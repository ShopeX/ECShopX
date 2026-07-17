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

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Entities\Activities;
use EmployeePurchaseBundle\Entities\ActivityItems;
use EmployeePurchaseBundle\Entities\ActivityGoods;
use EmployeePurchaseBundle\Entities\ActivityEnterprises;
use GoodsBundle\Entities\Items;
use GoodsBundle\Repositories\ItemsRepository;
use EmployeePurchaseBundle\Entities\ActivityPassphraseEnterprises;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsRelCatsService;
use GoodsBundle\Services\MultiLang\MultiLangService;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributorItemsService;
use WechatBundle\Services\WeappService;

class ActivitiesService
{
    public $entityRepository;
    public $itemsEntityRepository;
    public $enterpriseEntityRepository;
    public $passphraseEnterpriseRepository;

    /**
     * MemberService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Activities::class);
        $this->itemsEntityRepository = app('registry')->getManager('default')->getRepository(ActivityItems::class);
        $this->goodsEntityRepository = app('registry')->getManager('default')->getRepository(ActivityGoods::class);
        $this->enterpriseEntityRepository = app('registry')->getManager('default')->getRepository(ActivityEnterprises::class);
        $this->passphraseEnterpriseRepository = app('registry')->getManager('default')->getRepository(ActivityPassphraseEnterprises::class);
    }

    /**
     * @param mixed $rows
     * @return array<int,mixed>
     */
    public function normalizePassphraseRows($rows)
    {
        if ($rows === null || $rows === '') {
            return [];
        }
        if (is_string($rows)) {
            $decoded = json_decode($rows, true);

            return is_array($decoded) ? $decoded : [];
        }
        if (is_array($rows)) {
            return array_values($rows);
        }

        return [];
    }

    /**
     * @param mixed $enterpriseId
     * @return int[]
     */
    public function normalizeActivityEnterpriseIds($enterpriseId)
    {
        if (is_array($enterpriseId)) {
            return array_values(array_unique(array_map('intval', $enterpriseId)));
        }
        if ($enterpriseId === null || $enterpriseId === '') {
            return [];
        }
        if (is_string($enterpriseId)) {
            return array_values(array_unique(array_map('intval', array_filter(explode(',', $enterpriseId)))));
        }

        return [(int) $enterpriseId];
    }

    /**
     * 开启口令时：每行须含参与名额（>0）、口令码、**每企业口令通道额度(分)且须 ≥1**；未开启口令时返回空数组。
     *
     * @param int[] $allowedEnterpriseIds
     * @return array<int,array{enterprise_id:int,participate_quota:int,passphrase_code:string,passphrase_limitfee:int}>
     */
    public function validateAndBuildPassphraseRows($enabled, array $rawRows, array $allowedEnterpriseIds)
    {
        if (!$enabled) {
            return [];
        }
        if (empty($rawRows)) {
            throw new ResourceException('开启口令通道时请配置口令企业信息');
        }
        $built = [];
        $codes = [];
        $eids = [];
        foreach ($rawRows as $row) {
            if (is_object($row)) {
                $row = json_decode(json_encode($row), true);
            }
            if (!is_array($row)) {
                throw new ResourceException('口令企业配置格式错误');
            }
            $eid = (int) ($row['enterprise_id'] ?? 0);
            $quota = (int) ($row['participate_quota'] ?? $row['quota'] ?? 0);
            $rowLimit = $row['passphrase_limitfee'] ?? $row['limit_fee'] ?? null;
            if ($rowLimit === null || $rowLimit === '' || !is_numeric($rowLimit) || (int) $rowLimit < 1) {
                throw new ResourceException('口令通道额度须大于0（单位：分）');
            }
            $code = isset($row['passphrase_code']) ? trim((string) $row['passphrase_code']) : '';
            if ($code === '' && isset($row['code'])) {
                $code = trim((string) $row['code']);
            }
            if ($eid <= 0 || !in_array($eid, $allowedEnterpriseIds, true)) {
                throw new ResourceException('口令企业须为活动参与企业');
            }
            if ($quota <= 0) {
                throw new ResourceException('可参与名额须大于0');
            }
            if ($code === '' || strlen($code) > 64) {
                throw new ResourceException('口令编码为1-64个字符');
            }
            if (isset($codes[$code])) {
                throw new ResourceException('同一活动下口令编码不能重复');
            }
            $codes[$code] = true;
            if (isset($eids[$eid])) {
                throw new ResourceException('同一活动下企业不能重复配置口令');
            }
            $eids[$eid] = true;
            $built[] = [
                'enterprise_id' => $eid,
                'participate_quota' => $quota,
                'passphrase_code' => $code,
                'passphrase_limitfee' => (int) $rowLimit,
            ];
        }

        return $built;
    }

    /**
     * 口令编码在公司维度不可与其它活动已占用冲突（与生成接口去重策略一致）；更新时可排除本活动旧数据
     *
     * @param array<int,array{enterprise_id:int,participate_quota:int,passphrase_code:string,passphrase_limitfee:int}> $builtRows
     * @param int|null                                                                               $excludeActivityId 更新活动时传入当前活动 ID
     */
    public function assertPassphraseCodesAvailableForCompany($companyId, array $builtRows, $excludeActivityId = null)
    {
        if (empty($builtRows)) {
            return;
        }
        $wanted = [];
        foreach ($builtRows as $r) {
            $c = isset($r['passphrase_code']) ? trim((string) $r['passphrase_code']) : '';
            if ($c !== '') {
                $wanted[$c] = true;
            }
        }
        if (empty($wanted)) {
            return;
        }

        $excludeActivityId = $excludeActivityId !== null ? (int) $excludeActivityId : null;
        $existing = $this->passphraseEnterpriseRepository->getLists(['company_id' => (int) $companyId], 'activity_id,passphrase_code');
        foreach ($existing as $row) {
            $code = isset($row['passphrase_code']) ? trim((string) $row['passphrase_code']) : '';
            if ($code === '' || !isset($wanted[$code])) {
                continue;
            }
            $aid = (int) ($row['activity_id'] ?? 0);
            if ($excludeActivityId !== null && $aid === $excludeActivityId) {
                continue;
            }
            throw new ResourceException('口令编码已被其它活动占用：'.$code);
        }
    }

    /**
     * @param array<int,array{enterprise_id:int,participate_quota:int,passphrase_code:string,passphrase_limitfee:int}> $rows
     */
    public function syncPassphraseEnterprises($companyId, $activityId, $enabled, array $rows)
    {
        $oldRows = $this->passphraseEnterpriseRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
        ], 'enterprise_id,participate_quota');
        $oldEids = [];
        $oldQuotaByEnterprise = [];
        foreach ($oldRows as $or) {
            $e = (int) ($or['enterprise_id'] ?? 0);
            if ($e > 0) {
                $oldEids[$e] = true;
                $oldQuotaByEnterprise[$e] = (int) ($or['participate_quota'] ?? 0);
            }
        }

        if ($enabled && !empty($rows)) {
            foreach ($rows as $r) {
                $eid = (int) ($r['enterprise_id'] ?? 0);
                if ($eid <= 0) {
                    continue;
                }
                $newQuota = (int) ($r['participate_quota'] ?? 0);
                if (isset($oldQuotaByEnterprise[$eid]) && $newQuota < $oldQuotaByEnterprise[$eid]) {
                    $savedQuota = $oldQuotaByEnterprise[$eid];
                    $ent = (new EnterprisesService())->getEnterpriseInfo([
                        'company_id' => $companyId,
                        'id' => $eid,
                    ]);
                    $label = !empty($ent['name'])
                        ? ($ent['name'].'（企业 ID '.$eid.'）')
                        : ('企业 ID '.$eid);
                    throw new ResourceException(
                        $label.'：本活动可参与名额不得低于已保存值（已保存：'.$savedQuota.'，本次提交：'.$newQuota.'）'
                    );
                }
            }
        }

        $this->passphraseEnterpriseRepository->deleteBy(['company_id' => $companyId, 'activity_id' => $activityId]);

        $quotaRedis = new PassphraseParticipateQuotaRedisService();
        if (!$enabled || empty($rows)) {
            foreach (array_keys($oldEids) as $eid) {
                $quotaRedis->deleteKey((int) $companyId, (int) $activityId, (int) $eid);
            }

            return;
        }
        $now = time();
        $batch = [];
        $newEids = [];
        foreach ($rows as $r) {
            $batch[] = [
                'company_id' => $companyId,
                'activity_id' => $activityId,
                'enterprise_id' => $r['enterprise_id'],
                'participate_quota' => $r['participate_quota'],
                'passphrase_code' => $r['passphrase_code'],
                'passphrase_limitfee' => (int) ($r['passphrase_limitfee'] ?? 0),
                'created' => $now,
                'updated' => $now,
            ];
            $newEids[(int) $r['enterprise_id']] = true;
        }
        $this->passphraseEnterpriseRepository->batchInsert($batch);

        foreach ($rows as $r) {
            $eid = (int) $r['enterprise_id'];
            $newQuota = (int) $r['participate_quota'];
            $oldQuota = (int) ($oldQuotaByEnterprise[$eid] ?? 0);
            $quotaRedis->syncRemainingAfterQuotaSettingChange((int) $companyId, (int) $activityId, $eid, $oldQuota, $newQuota);
        }
        foreach (array_keys($oldEids) as $eid) {
            if (!isset($newEids[(int) $eid])) {
                $quotaRedis->deleteKey((int) $companyId, (int) $activityId, (int) $eid);
            }
        }
    }

    public function getPassphraseEnterpriseList($companyId, $activityId)
    {
        $rows = $this->passphraseEnterpriseRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
        ]);
        $eids = [];
        foreach ($rows as $r) {
            $e = (int) ($r['enterprise_id'] ?? 0);
            if ($e > 0) {
                $eids[] = $e;
            }
        }

        $enterprisesService = new EnterprisesService();
        $entMap = $enterprisesService->getEnterpriseInfoBatchMap((int) $companyId, $eids);

        $out = [];
        foreach ($rows as $r) {
            $eid = (int) ($r['enterprise_id'] ?? 0);
            $item = [
                'id' => $r['id'] ?? null,
                'company_id' => $r['company_id'] ?? null,
                'activity_id' => $r['activity_id'] ?? null,
                'enterprise_id' => $r['enterprise_id'] ?? null,
                'participate_quota' => $r['participate_quota'] ?? null,
                'passphrase_code' => $r['passphrase_code'] ?? null,
                'passphrase_limitfee' => isset($r['passphrase_limitfee']) ? (int) $r['passphrase_limitfee'] : null,
                'created' => $r['created'] ?? null,
                'updated' => $r['updated'] ?? null,
            ];
            if ($eid > 0 && isset($entMap[$eid])) {
                $item['enterprise'] = $entMap[$eid];
            } elseif ($eid > 0) {
                $item['enterprise'] = ['id' => $eid];
            } else {
                $item['enterprise'] = null;
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * 小程序等 C 端：当前活动+企业在口令表中的**可展示**配置（不含 passphrase_code，避免泄露）。
     *
     * @param array<string,mixed> $activity ActivitiesRepository::getInfo 单行
     * @return array{is_passphrase_enabled:0|1,passphrase_participate_quota:int|null,passphrase_limitfee:int|null} 后者两项为分/名额，未开口令或该企业无绑定时为 null
     */
    public function getPassphraseClientSummary(int $companyId, int $activityId, int $enterpriseId, array $activity)
    {
        $on = !empty($activity['is_passphrase_enabled']) ? 1 : 0;
        if ($on === 0) {
            return [
                'is_passphrase_enabled' => 0,
                'passphrase_participate_quota' => null,
                'passphrase_limitfee' => null,
            ];
        }
        $rows = $this->passphraseEnterpriseRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
            'enterprise_id' => $enterpriseId,
        ], 'participate_quota,passphrase_limitfee');
        if (empty($rows)) {
            return [
                'is_passphrase_enabled' => 1,
                'passphrase_participate_quota' => null,
                'passphrase_limitfee' => null,
            ];
        }
        $r = $rows[0];

        return [
            'is_passphrase_enabled' => 1,
            'passphrase_participate_quota' => isset($r['participate_quota']) ? (int) $r['participate_quota'] : null,
            'passphrase_limitfee' => isset($r['passphrase_limitfee']) ? (int) $r['passphrase_limitfee'] : null,
        ];
    }

    /**
     * 活动下某企业在口令表中的口令（无绑定行返回 null）
     */
    public function getPassphraseCodeForActivityEnterprise(int $companyId, int $activityId, int $enterpriseId): ?string
    {
        $rows = $this->passphraseEnterpriseRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
            'enterprise_id' => $enterpriseId,
        ], 'passphrase_code');
        if (empty($rows)) {
            return null;
        }
        $code = $rows[0]['passphrase_code'] ?? null;
        if ($code === null || $code === '') {
            return null;
        }

        return (string) $code;
    }

    /**
     * 用户输入的口令是否与库中该活动-企业绑定口令一致（不含活动存在性校验，请先 getInfo）
     *
     * @param array<string,mixed> $activity ActivitiesRepository::getInfo 单行
     */
    public function isActivityEnterprisePassphraseMatch(array $activity, int $enterpriseId, string $inputCode): bool
    {
        $companyId = (int) ($activity['company_id'] ?? 0);
        $activityId = (int) ($activity['id'] ?? 0);
        if ($companyId <= 0 || $activityId <= 0 || $enterpriseId <= 0) {
            return false;
        }
        $allowed = $this->normalizeActivityEnterpriseIds($activity['enterprise_id'] ?? []);
        if (!in_array($enterpriseId, $allowed, true)) {
            return false;
        }
        if (empty($activity['is_passphrase_enabled'])) {
            return false;
        }
        $stored = $this->getPassphraseCodeForActivityEnterprise($companyId, $activityId, $enterpriseId);
        if ($stored === null || $stored === '') {
            return false;
        }
        $in = trim($inputCode);
        if ($in === '') {
            return false;
        }

        return hash_equals($stored, $in);
    }

    /**
     * 活动已开口令、企业在参与范围内且口令表中有有效口令码（用于加车前自动建档判断）。
     */
    public function supportsPassphraseBypassWhitelist(int $companyId, int $activityId, int $enterpriseId): bool
    {
        $companyId = (int) $companyId;
        $activityId = (int) $activityId;
        $enterpriseId = (int) $enterpriseId;
        if ($companyId < 1 || $activityId < 1 || $enterpriseId < 1) {
            return false;
        }
        $activity = $this->getInfo(['company_id' => $companyId, 'id' => $activityId]);
        if (empty($activity) || empty($activity['is_passphrase_enabled'])) {
            return false;
        }
        $allowed = $this->normalizeActivityEnterpriseIds($activity['enterprise_id'] ?? []);
        if (!in_array($enterpriseId, $allowed, true)) {
            return false;
        }
        $code = $this->getPassphraseCodeForActivityEnterprise($companyId, $activityId, $enterpriseId);

        return $code !== null && $code !== '';
    }

    /**
     * 批量生成口令编码：8 位数字+大小写字母，与本批结果互不重复。
     * - 有 activity_id：与「该活动」已保存口令去重；企业须为该活动参与企业。
     * - 无 activity_id（新建活动）：与「本公司」下所有活动已占用口令去重；企业须为本公司（及店铺可见范围）有效内购企业。
     *
     * @param int      $companyId
     * @param int[]    $enterpriseIds
     * @param int      $countPerEnterprise
     * @param int      $activityId           0 表示未建活动
     * @param int|null $distributorScopeId   店铺操作员时为其 distributor_id；平台为 null
     * @return array{list: array<int, array{enterprise_id: int, passphrase_codes: string[]}>}
     */
    public function generatePassphraseCodesForEnterprises($companyId, array $enterpriseIds, $countPerEnterprise = 1, $activityId = 0, $distributorScopeId = null)
    {
        $companyId = (int) $companyId;
        $activityId = (int) $activityId;
        $countPerEnterprise = (int) $countPerEnterprise;
        if ($companyId <= 0) {
            throw new ResourceException('公司参数无效');
        }
        if ($countPerEnterprise < 1 || $countPerEnterprise > 50) {
            throw new ResourceException('每企业生成条数须在 1～50 之间');
        }
        $enterpriseIds = array_values(array_unique(array_filter(array_map('intval', $enterpriseIds))));
        if (empty($enterpriseIds)) {
            throw new ResourceException('请传入企业ID');
        }
        if (count($enterpriseIds) > 100) {
            throw new ResourceException('单次最多选择 100 个企业');
        }
        $totalSlots = count($enterpriseIds) * $countPerEnterprise;
        if ($totalSlots > 500) {
            throw new ResourceException('单次生成口令总数不能超过 500，请减少企业数量或每企业条数');
        }

        $used = [];

        if ($activityId > 0) {
            $activityFilter = ['company_id' => $companyId, 'id' => $activityId];
            if ($distributorScopeId !== null) {
                $activityFilter['distributor_id'] = (int) $distributorScopeId;
            }
            $activity = $this->entityRepository->getInfo($activityFilter);
            if (empty($activity)) {
                throw new ResourceException('活动不存在');
            }

            $allowedEnterpriseIds = $this->normalizeActivityEnterpriseIds($activity['enterprise_id'] ?? []);
            foreach ($enterpriseIds as $eid) {
                if ($eid <= 0 || !in_array($eid, $allowedEnterpriseIds, true)) {
                    throw new ResourceException('企业须为本活动参与企业');
                }
            }

            $existingRows = $this->passphraseEnterpriseRepository->getLists(['activity_id' => $activityId], 'passphrase_code');
        } else {
            $this->assertEnterprisesBelongToCompanyScope($companyId, $enterpriseIds, $distributorScopeId);
            $existingRows = $this->passphraseEnterpriseRepository->getLists(['company_id' => $companyId], 'passphrase_code');
        }

        foreach ($existingRows as $row) {
            $c = isset($row['passphrase_code']) ? trim((string) $row['passphrase_code']) : '';
            if ($c !== '') {
                $used[$c] = true;
            }
        }

        $charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charsetLen = strlen($charset);
        $list = [];
        foreach ($enterpriseIds as $eid) {
            $codes = [];
            for ($n = 0; $n < $countPerEnterprise; $n++) {
                $codes[] = $this->rollUniquePassphraseCode8($charset, $charsetLen, $used);
            }
            $list[] = [
                'enterprise_id' => $eid,
                'passphrase_codes' => $codes,
            ];
        }

        return ['list' => $list];
    }

    /**
     * @param int[]    $enterpriseIds
     * @param int|null $distributorScopeId
     */
    private function assertEnterprisesBelongToCompanyScope($companyId, array $enterpriseIds, $distributorScopeId)
    {
        $enterprisesService = new EnterprisesService();
        $filter = [
            'company_id' => $companyId,
            'id' => $enterpriseIds,
        ];
        if ($distributorScopeId !== null) {
            $filter['distributor_id'] = (int) $distributorScopeId;
        }
        $found = $enterprisesService->enterprisesRepository->getLists($filter, 'id');
        $foundIds = [];
        foreach ($found as $row) {
            if (isset($row['id'])) {
                $foundIds[(int) $row['id']] = true;
            }
        }
        foreach ($enterpriseIds as $eid) {
            if ($eid <= 0 || !isset($foundIds[$eid])) {
                throw new ResourceException('企业不存在或无权操作');
            }
        }
    }

    /**
     * @param array<string,bool> $used
     */
    private function rollUniquePassphraseCode8($charset, $charsetLen, array &$used)
    {
        $maxAttempts = 300;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $charset[random_int(0, $charsetLen - 1)];
            }
            if (!isset($used[$code])) {
                $used[$code] = true;

                return $code;
            }
        }
        throw new ResourceException('生成唯一口令失败，请重试');
    }

    public function create($data)
    {
        $rawPassphraseRows = $this->normalizePassphraseRows($data['passphrase_enterprises'] ?? []);
        unset($data['passphrase_enterprises']);
        unset($data['passphrase_limitfee']);

        $enabled = !empty($data['is_passphrase_enabled']);
        $data['is_passphrase_enabled'] = $enabled;

        $allowedIds = $this->normalizeActivityEnterpriseIds($data['enterprise_id'] ?? []);
        $passphraseRows = $this->validateAndBuildPassphraseRows($enabled, $rawPassphraseRows, $allowedIds);
        $this->assertPassphraseCodesAvailableForCompany((int) $data['company_id'], $passphraseRows, null);

        $result = $this->entityRepository->create($data);
        $enterpriseData = [];
        foreach ($data['enterprise_id'] as $enterpriseId) {
            $enterpriseData[] = [
                'activity_id' => $result['id'],
                'enterprise_id' => $enterpriseId,
                'company_id' => $result['company_id'],
            ];
        }
        $this->enterpriseEntityRepository->batchInsert($enterpriseData);
        $this->syncPassphraseEnterprises($result['company_id'], $result['id'], $enabled, $passphraseRows);

        return $result;
    }

    public function updateActivity($filter, $data)
    {
        $passphraseSync = $data['__passphrase_sync'] ?? 'none';
        unset($data['__passphrase_sync']);
        $rawPassphrasePayload = $data['passphrase_enterprises'] ?? null;
        unset($data['passphrase_enterprises']);
        unset($data['passphrase_limitfee']);

        $result = $this->entityRepository->updateOneBy($filter, $data);
        if (isset($data['enterprise_id']) && $data['enterprise_id']) {
            $this->enterpriseEntityRepository->deleteBy(['company_id' => $result['company_id'], 'activity_id' => $result['id']]);
            $enterpriseData = [];
            foreach ($data['enterprise_id'] as $enterpriseId) {
                $enterpriseData[] = [
                    'activity_id' => $result['id'],
                    'enterprise_id' => $enterpriseId,
                    'company_id' => $result['company_id'],
                ];
            }
            $this->enterpriseEntityRepository->batchInsert($enterpriseData);
        }

        if ($passphraseSync === 'clear') {
            $this->syncPassphraseEnterprises($result['company_id'], $result['id'], false, []);
        } elseif ($passphraseSync === 'replace') {
            $enabled = !empty($result['is_passphrase_enabled']);
            $allowedIds = $this->normalizeActivityEnterpriseIds($result['enterprise_id']);
            $rawRows = $this->normalizePassphraseRows($rawPassphrasePayload);
            $rows = $this->validateAndBuildPassphraseRows($enabled, $rawRows, $allowedIds);
            $this->assertPassphraseCodesAvailableForCompany((int) $result['company_id'], $rows, (int) $result['id']);
            $this->syncPassphraseEnterprises($result['company_id'], $result['id'], $enabled, $rows);
        }

        return $result;
    }

    public function cancelActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['display_time'] < time()) {
            throw new ResourceException('只能取消未开始的活动');
        }

        return $this->entityRepository->updateBy($filter, ['status' => 'cancel']);
    }

    public function suspendActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['status'] != 'active') {
            throw new ResourceException('只能暂停进行中的活动');
        }

        $now = time();
        if (($activity['employee_begin_time'] > $now && $activity['relative_begin_time'] > $now) || ($activity['employee_end_time'] < $now && $activity['relative_end_time'] < $now)) {
            throw new ResourceException('只能暂停进行中的活动');
        }

        return $this->entityRepository->updateBy($filter, ['status' => 'pending']);
    }

    public function activeActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['status'] != 'pending') {
            throw new ResourceException('只能开始暂停中的活动');
        }

        return $this->entityRepository->updateBy($filter, ['status' => 'active']);
    }

    public function endActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['display_time'] > time()) {
            throw new ResourceException('只能结束已开始的活动');
        }

        return $this->entityRepository->updateBy($filter, ['status' => 'over']);
    }

    public function aheadActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['status'] != 'active') {
            throw new ResourceException('只能提前开始有效的活动');
        }

        $now = time();
        if ($activity['display_time'] > $now || $activity['employee_begin_time'] < $now) {
            throw new ResourceException('只能提前开始预热中的活动');
        }

        return $this->entityRepository->updateBy($filter, ['employee_begin_time' => $now]);
    }

    public function getActivityList($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = ['created' => 'DESC'])
    {
        if (isset($filter['enterprise_id']) && $filter['enterprise_id']) {
            $enterpriseList = $this->enterpriseEntityRepository->getLists(['company_id' => $filter['company_id'], 'enterprise_id' => $filter['enterprise_id']], 'activity_id');
            if (!$enterpriseList) {
                return ['total_count' => 0, 'list' => []];
            }
            $filter['id'] = array_unique(array_column($enterpriseList, 'activity_id'));
        }
        unset($filter['enterprise_id']);

        $result = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (!$result['total_count']) {
            return $result;
        }
        $distributorService = new DistributorService();
        $storeIds = array_filter(array_unique(array_column($result['list'], 'distributor_id')), function ($distributorId) {
            return is_numeric($distributorId) && $distributorId >= 0;
        });
        $storeData = [];
        if ($storeIds) {
            $storeList = $distributorService->getDistributorOriginalList([
                'company_id' => $filter['company_id'],
                'distributor_id' => $storeIds,
            ], 1, $pageSize);
            $storeData = array_column($storeList['list'], null, 'distributor_id');
            // 附加总店信息
            $storeData[0] = $distributorService->getDistributorSelfSimpleInfo($filter['company_id']);
        }
        foreach ($result['list'] as $key => $row) {
            $result['list'][$key]['price_display_config'] = json_decode($row['price_display_config'], true);
            $result['list'][$key]['distributor_name'] = isset($row['distributor_id']) ? ($storeData[$row['distributor_id']]['name'] ?? '') : '';
        }

        return $result;
    }

    /**
     * 活动商品列表行多语言（与 GoodsBundle\Repositories\ItemsRepository::list 一致）
     *
     * 前端展示名使用 itemName：仅当行上已存在 itemName 时，用多语言后的 item_name 覆盖。
     *
     * @param array $list getActivityItemsList 返回的列表（可含 spec_items 子项）
     * @return array
     */
    public function applyMultiLangToActivityItemList(array $list)
    {
        if ($list === []) {
            return $list;
        }
        /** @var ItemsRepository $itemsRepository */
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $multiLangField = ['item_name', 'brief', 'intro'];
        $table = 'items';
        $lang = $itemsRepository->getLang();
        $service = new MultiLangService();
        $list = $service->getListAddLang($list, $multiLangField, $table, $lang, 'item_id');
        foreach ($list as $k => $row) {
            if ( isset($row['itemName']) ) {
                $list[$k]['itemName'] = $list[$k]['item_name'] ?? '';
            }
            if (!empty($row['spec_items']) && is_array($row['spec_items'])) {
                $list[$k]['spec_items'] = $service->getListAddLang($row['spec_items'], $multiLangField, $table, $lang, 'item_id');
            }
        }

        return $list;
    }

    /**
     * 活动参与企业导出：企业名称、编码、口令码、可参与名额、口令码额度（元）、扫码落地页小程序码下载 URL
     *
     * @param int      $companyId
     * @param int      $activityId
     * @param int|null $distributorScopeId 店铺账号时为其 distributor_id；平台为 null
     * @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}> 每行六列，供 Excel 导出
     */
    public function buildActivityEnterpriseQrcodeExportRows(int $companyId, int $activityId, $distributorScopeId = null): array
    {
        $filter = ['company_id' => $companyId, 'id' => $activityId];
        if ($distributorScopeId !== null) {
            $filter['distributor_id'] = (int) $distributorScopeId;
        }
        $activity = $this->entityRepository->getInfo($filter);
        if (empty($activity)) {
            throw new ResourceException('活动不存在');
        }

        $participations = $this->enterpriseEntityRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
        ], 'enterprise_id', 1, -1, ['enterprise_id' => 'ASC']);

        $eids = [];
        foreach ($participations as $p) {
            $e = (int) ($p['enterprise_id'] ?? 0);
            if ($e > 0) {
                $eids[] = $e;
            }
        }

        $enterprisesService = new EnterprisesService();
        $entMap = $enterprisesService->getEnterpriseInfoBatchMap($companyId, $eids);

        $weappService = new WeappService();
        $wxaAppid = $weappService->getWxappidByTemplateName($companyId, 'yykweishop');
        if (!$wxaAppid) {
            throw new ResourceException('没有绑定小程序');
        }

        $passphraseEnabled = !empty($activity['is_passphrase_enabled']);
        $passphraseByEnterprise = [];
        if ($passphraseEnabled) {
            $passphraseRows = $this->passphraseEnterpriseRepository->getLists([
                'company_id' => $companyId,
                'activity_id' => $activityId,
            ], 'enterprise_id,participate_quota,passphrase_limitfee,passphrase_code');
            foreach ($passphraseRows as $pr) {
                $peid = (int) ($pr['enterprise_id'] ?? 0);
                if ($peid > 0) {
                    $passphraseByEnterprise[$peid] = $pr;
                }
            }
        }
        $baseUrl = rtrim((string) (config('app.url') ?: env('APP_URL', '')), '/');
        if ($baseUrl === '') {
            $baseUrl = 'http://localhost';
        }

        $rows = [];
        foreach ($participations as $p) {
            $eid = (int) ($p['enterprise_id'] ?? 0);
            if ($eid <= 0) {
                continue;
            }
            $ent = $entMap[$eid] ?? [];
            $name = (string) ($ent['name'] ?? '');
            $sn = (string) ($ent['enterprise_sn'] ?? '');
            $pass = '';
            $participateQuota = '';
            $passphraseLimitfee = '';
            if ($passphraseEnabled) {
                $passphraseRow = $passphraseByEnterprise[$eid] ?? null;
                if ($passphraseRow !== null) {
                    $code = $passphraseRow['passphrase_code'] ?? null;
                    $pass = ($code !== null && $code !== '') ? (string) $code : '';
                    $participateQuota = (string) (int) ($passphraseRow['participate_quota'] ?? 0);
                    $limitFen = (int) ($passphraseRow['passphrase_limitfee'] ?? 0);
                    $passphraseLimitfee = number_format($limitFen / 100, 2, '.', '');
                }
            }
            // 与 WechatBundle Qrcode::getQrcode 一致：服务端用 company_id 取小程序；其余参数经 getShareId 落 Redis，scene 仅 share_id。
            // cid 会随 getShareId 写入 Redis（company_id 在 getQrcode 里会从入参剔除），落地页 getByShareId 可带回商户与活动上下文。
            $query = [
                'company_id' => $companyId,
                'cid' => $companyId,
                'temp_name' => 'yykweishop',
                'page' => 'pages/share-land',
                'id' => $activityId,
                'enterprise_id' => $eid,
                'appid' => $wxaAppid,
                'from_scene' => 'poster_purchase_auth',
            ];
            if ($passphraseEnabled) {
                $query['ppe'] = '1';
            }
            $qrcodeUrl = $baseUrl.'/wechatAuth/wxapp/qrcode.png?'.http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            );
            $rows[] = [$name, $sn, $pass, $participateQuota, $passphraseLimitfee, $qrcodeUrl];
        }

        return $rows;
    }

    public function getActivityItemList($filter, $page, $pageSize, $itemSpec = false, $isDefault = false, $orderBy = ['item_id' => 'desc'])
    {
        $distributorId = $filter['distributor_id'] ?? 0;
        $shelfStatus = $filter['shelf_status'] ?? null;
        $itemIds = $filter['item_id'] ?? [];
        unset($filter['distributor_id'], $filter['shelf_status']);
        $result = $this->goodsEntityRepository->getActivityGoodsList($filter, $page, $pageSize, $orderBy);
        if ($result['list']) {
            $result['list'] = $this->itemsEntityRepository->getActivityItemsList(
                $filter['company_id'],
                $filter['activity_id'],
                array_column($result['list'], 'goods_id'),
                $itemSpec,
                $isDefault,
                $orderBy,
                $shelfStatus
            );
            if ($itemIds) {
                $itemIds = array_map('intval', (array) $itemIds);
                foreach ($result['list'] as &$item) {
                    if (!empty($item['spec_items'])) {
                        $item['spec_items'] = array_values(array_filter($item['spec_items'], static function ($specItem) use ($itemIds) {
                            return in_array((int) $specItem['item_id'], $itemIds, true);
                        }));
                    }
                }
                unset($item);
            }
            if ($distributorId > 0) {
                // 查询店铺商品的是否为总库库存、店铺库存字段
                $itemIds = array_column($result['list'], 'item_id');
                $distributorItemsService = new DistributorItemsService();
                $distributorItemsList = $distributorItemsService->getDistributorRelItemList([
                    'company_id' => $filter['company_id'],
                    'item_id' => $itemIds,
                    'distributor_id' => $distributorId,
                ], $pageSize, 1, ['item_id' => 'desc'], false);
                $distributorItemsList = array_column($distributorItemsList['list'], null, 'item_id');
            }
            $itemsService = new ItemsService();
            foreach ($result['list'] as &$row) {
                if ($row['nospec'] === false || $row['nospec'] === 'false' || $row['nospec'] === 0 || $row['nospec'] === '0') {
                    $row['total_item_spec'] = $itemsService->count(['company_id' => $row['company_id'], 'goods_id' => $row['goods_id']]);
                }
                if ($distributorId > 0) {
                    $row['store'] = $distributorItemsList[$row['item_id']]['store'] ?? 0;
                }
            }
            unset($row);
            $result['list'] = $this->applyMultiLangToActivityItemList($result['list']);
        }
        return $result;
    }

    public function addActivityItems($params)
    {
        $activity = $this->entityRepository->getInfo(['id' => $params['activity_id']]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        $itemsService = new ItemsService();
        $items = $itemsService->getItems($params['item_id'], $params['company_id'], 'item_id,goods_id,price,store');
        if (!$items) {
            throw new ResourceException('请选择活动商品');
        }

        $itemsData = [];
        $goodsData = [];
        foreach ($items as $item) {
            $itemsData[] = [
                'activity_id' => $activity['id'],
                'company_id' => $activity['company_id'],
                'item_id' => $item['item_id'],
                'goods_id' => $item['goods_id'],
                'activity_price' => $item['price'],
                'activity_store' => 0,
                'shelf_status' => 1,
            ];

            $goodsData[$item['goods_id']] = [
                'activity_id' => $activity['id'],
                'company_id' => $activity['company_id'],
                'goods_id' => $item['goods_id'],
            ];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->itemsEntityRepository->batchInsert($itemsData);
            $this->goodsEntityRepository->batchInsert(array_values($goodsData));
            // 更新活动关联的商品分类
            $activityItemsService = new ActivityItemsService();
            $activityItemsService->storeActivityItemsCategory($params['company_id'], $params['activity_id']);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    public function addActivityItemsByCategory($params)
    {
        $itemsCategoryService = new ItemsCategoryService();
        $catIds = $itemsCategoryService->getItemsCategoryIds($params['cat_id'], $params['company_id']);

        $itemsRelCatsService = new ItemsRelCatsService();
        $itemsService = new ItemsService();
        $filter = [
            'company_id' => $params['company_id'],
            'category_id' => $catIds,
        ];

        $page = 1;
        $pageSize = 200;
        do {
            $relCatList = $itemsRelCatsService->getList($filter, 'item_id', $page, $pageSize, ['item_id' => 'ASC']);
            if (!$relCatList) {
                break;
            }
            $itemIds = array_column($relCatList, 'item_id');
            if (isset($params['distributor_id']) && $params['distributor_id'] > 0) {
                $distributorItemsService = new DistributorItemsService();
                $distributorItemsList = $distributorItemsService->getDistributorRelItemList([
                    'company_id' => $params['company_id'],
                    'item_id' => $itemIds,
                    'distributor_id' => $params['distributor_id'],
                ], $pageSize, 1, ['item_id' => 'desc'], false);
                if ($distributorItemsList['total_count'] == 0) {
                    break;
                }
                $distributorItemIds = array_column($distributorItemsList['list'], 'item_id');
                $itemIds = array_intersect((array)$itemIds, $distributorItemIds);
            }
            $list = $itemsService->getItems($itemIds, $params['company_id'], 'item_id', 'default_item_id');
            if (!$list) {
                break;
            }
            $params['item_id'] = array_column($list, 'item_id');
            $this->addActivityItems($params);
            $page++;
        } while (count($list) == $pageSize);

        return true;
    }

    public function addActivityItemsByMainCategory($params)
    {
        $itemsCategoryService = new ItemsCategoryService();
        $mainCatIds = $params['main_cat_id'];
        foreach ($params['main_cat_id'] as $mainCatId) {
            $mainCatIds = array_merge($mainCatIds, $itemsCategoryService->getMainCatChildIdsBy($mainCatId, $params['company_id']));
        }
        $filter = [
            'company_id' => $params['company_id'],
            'item_category' => $mainCatIds,
        ];
        if (isset($params['distributor_id']) && $params['distributor_id'] > 0) {
            $distributorItemsService = new DistributorItemsService();
            $distributorItemsList = $distributorItemsService->getDistributorRelItemList([
                'company_id' => $params['company_id'],
                'item_category' => $mainCatIds,
                'distributor_id' => $params['distributor_id'],
            ], -1, 1, ['item_id' => 'desc'], false);
            if ($distributorItemsList['total_count'] == 0) {
                return true;
            }
            $filter['item_id'] = array_column($distributorItemsList['list'], 'item_id');
            unset($filter['item_category']);
        }

        $itemsService = new ItemsService();
        $page = 1;
        $pageSize = 500;
        do {
            $list = $itemsService->getLists($filter, 'item_id', $page, $pageSize, ['item_id' => 'ASC']);
            if (!$list) {
                break;
            }
            $params['item_id'] = array_column($list, 'item_id');
            $this->addActivityItems($params);
            $page++;
        } while (count($list) == $pageSize);

        return true;
    }

    public function updateActivityItems($filter, $data)
    {
        return $this->itemsEntityRepository->updateBy($filter, $data);
    }

    public function updateActivityItemsByGoods($filter, $data)
    {
        $item = $this->itemsEntityRepository->getInfo($filter);
        if (!$item) {
            throw new ResourceException('活动商品不存在');
        }

        $filter['goods_id'] = $item['goods_id'];
        unset($filter['item_id']);

        return $this->itemsEntityRepository->updateBy($filter, $data);
    }

    public function deleteActivityItems($filter, $allSpec = false)
    {
        $item = $this->itemsEntityRepository->getInfo($filter);
        if (!$item) {
            return true;
        }

        if ($allSpec) {
            $filter['goods_id'] = $item['goods_id'];
            unset($filter['item_id']);
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->itemsEntityRepository->deleteBy($filter);
            $filter['goods_id'] = $item['goods_id'];
            unset($filter['item_id']);
            $exist = $this->itemsEntityRepository->count($filter);
            if (!$exist) {
                $this->goodsEntityRepository->deleteBy($filter);
            }
            // 更新活动关联的商品分类
            $activityItemsService = new ActivityItemsService();
            $activityItemsService->storeActivityItemsCategory($filter['company_id'], $filter['activity_id']);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
        return true;
    }

    public function getActivityItemInfo($filter)
    {
        return $this->itemsEntityRepository->getInfo($filter);
    }

    public function getActivityEnterprises($filter)
    {
        $enterpriseList = $this->enterpriseEntityRepository->getLists($filter, 'enterprise_id');
        return $enterpriseList;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
