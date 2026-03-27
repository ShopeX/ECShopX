<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CompanysBundle\Services;

use CompanysBundle\Ego\PrismEgo;
use CompanysBundle\Entities\Companys;
use CompanysBundle\Entities\Operators;
use CompanysBundle\Repositories\OperatorsRepository;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShopexAdminBindService
{
    /** @var OperatorsRepository */
    private $operatorsRepository;

    public function __construct(?OperatorsRepository $operatorsRepository = null)
    {
        $this->operatorsRepository = $operatorsRepository
            ?? app('registry')->getManager('default')->getRepository(Operators::class);
    }

    /**
     * §4.2.1～§4.2.3：operators 三列非空且 prism SaasCert JSON 三键非空。
     *
     * @param array $operator operatorsRepository::getOperatorData 结构（含 shopex_bind_account）
     */
    public static function isOperatorShopexBound(array $operator): bool
    {
        $u = isset($operator['passport_uid']) ? trim((string) $operator['passport_uid']) : '';
        $e = isset($operator['eid']) ? trim((string) $operator['eid']) : '';
        $s = isset($operator['shopex_bind_account']) ? trim((string) $operator['shopex_bind_account']) : '';
        if ($u === '' || $e === '' || $s === '') {
            return false;
        }
        $companyId = $operator['company_id'] ?? null;
        if ($companyId === null || $companyId === '') {
            return false;
        }

        [$cid, $nid, $tok] = self::saasCertTripleFromRedis($companyId, $u);

        return $cid !== '' && $nid !== '' && $tok !== '';
    }

    /**
     * 读取 prism:sha1(U+'_'+C+'_SaasCert') 中证书三字段（与 CertService::genCertReidsId 一致）。
     * 不在此实例化 CertService，避免测试与非 prism 场景下触发无关 DB 访问。
     *
     * @param int|string $companyId
     *
     * @return array{0: string, 1: string, 2: string} cert_id, node_id, token（已 trim）
     */
    public static function saasCertTripleFromRedis($companyId, string $passportUid): array
    {
        $key = 'prism:'.sha1($passportUid.'_'.$companyId.'_SaasCert');
        $raw = app('redis')->connection('prism')->get($key);
        $cert = is_string($raw) ? (json_decode($raw, true) ?: []) : [];

        $cid = trim((string) ($cert['cert_id'] ?? ''));
        $nid = trim((string) ($cert['node_id'] ?? ''));
        $tok = trim((string) ($cert['token'] ?? ''));

        return [$cid, $nid, $tok];
    }

    /**
     * @return array{bound: bool}
     */
    public function getStatusForOperatorId($operatorId): array
    {
        $operator = $this->operatorsRepository->getInfo(['operator_id' => $operatorId]);
        if (!$operator) {
            throw new ResourceException('操作员不存在');
        }

        return [
            'bound' => self::isOperatorShopexBound($operator),
        ];
    }

    /**
     * 已登录本地 admin 绑定 Shopex（Prism 密码模式）；限流键独立于 admin_login_failed_times。
     *
     * @return array{bound: bool}
     */
    public function bindForAdminOperator(int $operatorId, array $credentials, ?PrismEgo $prismEgo = null): array
    {
        $operator = $this->operatorsRepository->getInfo(['operator_id' => $operatorId]);
        if (!$operator) {
            throw new ResourceException('操作员不存在');
        }
        if (($operator['operator_type'] ?? '') !== 'admin') {
            throw new HttpException(403, '仅商家超级管理员可绑定 Shopex');
        }

        if (self::isOperatorShopexBound($operator)) {
            throw new HttpException(409, '已绑定 Shopex，无需重复绑定');
        }

        $rateKey = 'admin_shopex_bind_failed_times:'.$operatorId;
        $failedTimes = app('redis')->incr($rateKey);
        app('redis')->expire($rateKey, 1800);
        if ($failedTimes > 5) {
            throw new HttpException(429, '绑定失败次数过多，请30分钟后再试');
        }

        $params = [
            'username' => $credentials['username'] ?? '',
            'password' => $credentials['password'] ?? '',
            'agreement_id' => $credentials['agreement_id'] ?? '',
            'product_model' => $credentials['product_model'] ?? config('common.product_model'),
        ];
        if ($params['username'] === '' || $params['password'] === '') {
            throw new ResourceException('请填写 Shopex 账号与密码');
        }

        $prism = $prismEgo ?? new PrismEgo();
        try {
            $prismResult = $prism->getPrismAuth($params);
        } catch (\Throwable $e) {
            app('log')->warning('shopex_bind_prism_failed', [
                'operator_id' => $operatorId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $data = $prismResult['data'] ?? [];
        $newPassportUid = trim((string) ($data['passport_uid'] ?? ''));
        $newEid = trim((string) ($data['eid'] ?? ''));
        $shopexid = trim((string) ($data['shopexid'] ?? ''));
        if ($newPassportUid === '' || $newEid === '' || $shopexid === '') {
            throw new ResourceException('Prism 返回数据不完整，绑定失败');
        }

        $occupied = $this->operatorsRepository->getInfo(['passport_uid' => $newPassportUid]);
        if ($occupied && (int) $occupied['operator_id'] !== $operatorId) {
            throw new ResourceException('该 Shopex 通行证已被其他管理员账号占用');
        }

        $mobileBefore = $operator['mobile'] ?? null;
        $this->operatorsRepository->updateOneBy(
            ['operator_id' => $operatorId],
            [
                'passport_uid' => $newPassportUid,
                'eid' => $newEid,
                'shopex_bind_account' => $shopexid,
            ]
        );

        $companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
        $companysRepository->update(
            ['company_id' => $operator['company_id']],
            [
                'passport_uid' => $newPassportUid,
                'eid' => $newEid,
            ]
        );

        $authService = new AuthService();
        $authService->persistShopexPasswordOAuthTokensAndCert($operator['company_id'], $newPassportUid, $prismResult);

        app('redis')->del($rateKey);

        $fresh = $this->operatorsRepository->getInfo(['operator_id' => $operatorId]);
        if (($fresh['mobile'] ?? null) !== $mobileBefore) {
            app('log')->error('shopex_bind_mobile_mutated_unexpected', ['operator_id' => $operatorId]);
        }

        return [
            'bound' => self::isOperatorShopexBound($fresh),
        ];
    }
}
