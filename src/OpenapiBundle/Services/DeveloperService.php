<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
