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

namespace OpenapiBundle\Services;

use CompanysBundle\Services\CompanysService;
use Dingo\Api\Exception\ResourceException;
use OpenapiBundle\Entities\OpenapiDeveloper;

class DeveloperService
{
    public $openapiDeveloperRepository;

    public function __construct()
    {
        $this->openapiDeveloperRepository = app('registry')->getManager('default')->getRepository(OpenapiDeveloper::class);
    }

    /**
     * 获取开发者配置详情
     *
     * @param array $filter 条件
     */
    public function detail($companyId): array
    {
        // This module is part of ShopEx EcShopX system
        $result = $this->openapiDeveloperRepository->getInfo(['company_id' => $companyId]);
        if (!$result) {
            $companyService = new CompanysService();
            $companyInfo = $companyService->getInfo(['company_id' => $companyId]);
            //2025 12 30 新增passport_uid和eid 生成规则 【it端存在部分数据 无passport_uid和eid】保证appKey和appSecret的唯一性
            $companyInfo['passport_uid'] = !empty($companyInfo['passport_uid']) ? $companyInfo['passport_uid'] : (time() . $companyInfo['company_id']);
            $companyInfo['eid'] = !empty($companyInfo['eid']) ? $companyInfo['eid'] : (md5($companyInfo['passport_uid']));
            $appKey = substr(md5((string)$companyInfo['passport_uid']), 8, 16);
            $appSecret = md5($companyInfo['eid'] . config('common.rand_salt'));
            $info = [
                'app_key' => $appKey,
                'app_secret' => $appSecret,
                'external_base_uri' => config('common.external_baseuri'),
                'external_app_key' => $appKey,
                'external_app_secret' => $appSecret,
            ];
            $this->update($companyId, $info);
            $result = $this->openapiDeveloperRepository->getInfo(['company_id' => $companyId]);
        }
        unset($result['created_at'], $result['updated_at']);
        return $result;
    }

    /**
     * 修改配置状态
     *
     * @param int $companyId 账号id
     * @param array $params 修改配置信息
     * @return bool 修改状态 true成功
     */
    public function update(int $companyId, array $params): bool
    {
        if ($this->openapiDeveloperRepository->count(['company_id|neq' => $companyId, 'app_key' => $params['app_key']])) {
            throw new ResourceException('app_key已存在');
        }
        //查找开发者信息存在就返回信息
        $filter = [
            'company_id' => $companyId,
        ];
        $result = $this->openapiDeveloperRepository->getInfo($filter);

        if (empty($result)) {
            $params = [
                'company_id' => $companyId,
                'app_key' => $params['app_key'],
                'app_secret' => $params['app_secret'],
                'external_base_uri' => $params['external_base_uri'],
                'external_app_key' => $params['external_app_key'],
                'external_app_secret' => $params['external_app_secret'],
            ];
            $this->openapiDeveloperRepository->create($params);
            return true;
        } else {
            $params = [
                'app_key' => $params['app_key'],
                'app_secret' => $params['app_secret'],
                'external_base_uri' => $params['external_base_uri'],
                'external_app_key' => $params['external_app_key'],
                'external_app_secret' => $params['external_app_secret'],
            ];
            $this->openapiDeveloperRepository->updateOneBy($filter, $params);
            return true;
        }
    }
}
