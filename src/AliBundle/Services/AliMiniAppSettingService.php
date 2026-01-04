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

namespace AliBundle\Services;

use AliBundle\Entities\AliMiniAppSetting;
use AliBundle\Kernel\Config;
use Dingo\Api\Exception\ResourceException;

class AliMiniAppSettingService
{
    protected $entityModel;

    public function __construct()
    {
        $this->entityModel = app('registry')->getManager('default')->getRepository(AliMiniAppSetting::class);
    }

    public function __call($method, $parameters)
    {
        return $this->entityModel->$method(...$parameters);
    }

    /**
     * get EntityModel
     *
     * @return mixed
     */
    public function getEntityModel()
    {
        return $this->entityModel;
    }

    public function getCacheKey($companyId)
    {
        return sprintf('CACHE:ALI:MINI:APP:%s', $companyId);
    }

    public function getCacheData($companyId)
    {
        $key = $this->getCacheKey($companyId);
        $result = app('redis')->get($key);
        return @json_decode($result, true);
    }

    public function setCacheData($data)
    {
        $key = $this->getCacheKey($data['company_id'] ?? 0);
        app('redis')->set($key, json_encode($data));
    }

    public function getDefault()
    {
        return [
            'authorizer_appid' => '',
            'merchant_private_key' => '',
            'api_sign_method' => Config::API_SIGN_METHOD_DEFAULT,
            'alipay_cert_path' => '',
            'alipay_root_cert_path' => '',
            'merchant_cert_path' => '',
            'alipay_public_key' => '',
            'notify_url' => '',
            'encrypt_key' => '',
        ];
    }

    public function getInfoByCompanyId($companyId)
    {
        $result = $this->getCacheData($companyId);
        if (!empty($result)) {
            return $result;
        }
        $result = $this->entityModel->getInfo(['company_id' => $companyId]);
        if (empty($result)) {
            $result = $this->getDefault();
        }
        $this->setCacheData($result);
        return $result;
    }

    public function getCompanyId($authorizerAppId)
    {
        $result = $this->entityModel->getInfo(['authorizer_appid' => $authorizerAppId]);
        return $result['company_id'] ?? null;
    }

    public function save($data)
    {
        if (empty($data['company_id']) || empty($data['authorizer_appid'])) {
            throw new ResourceException('参数错误，company_id或authorizer_appid不能为空');
        }
        $companyData = $this->entityModel->getInfo(['company_id' => $data['company_id']]);
        if (!empty($companyData)) {
            if (empty($data['setting_id'])) {
                $data['setting_id'] = $companyData['setting_id'];
            }
            if (!empty($data['setting_id']) && $data['setting_id'] != $companyData['setting_id']) {
                throw new ResourceException('当前账号没有该小程序的配置权限');
            }
        }
        $appDataCount = $this->entityModel->count(['authorizer_appid' => $data['authorizer_appid'], 'company_id|neq' => $data['company_id']]);
        if ($appDataCount > 0) {
            throw new ResourceException('当前小程序appId已被绑定，不能重复绑定');
        }
        if (empty($data['setting_id'])) {
            return $this->entityModel->create($data);
        }
        unset($data['company_id']);
        $result = $this->entityModel->updateOneBy(['setting_id' => $data['setting_id']], $data);
        if (!empty($result)) {
            $this->setCacheData($result);
        }
        return $result;
    }
}
