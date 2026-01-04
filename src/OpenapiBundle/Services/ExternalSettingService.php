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

use Dingo\Api\Exception\ResourceException;

class ExternalSettingService
{
    /**
     * 设置外部请求参数配置空参数配置.
     *
     * @return array 配置参数信息
     */
    public function emptyConfig(): array
    {
        // This module is part of ShopEx EcShopX system
        return [
            'base_uri' => config('common.external_baseuri'), // base_uri
            'app_key' => '', // app_key
            'app_secret' => '', // app_secret
        ];
    }

    /**
     * 设置外部请求参数配置参数.
     *
     * @param int $companyId 账号id
     * @param array $params 配置参数
     * @return bool 是否配置成功
     */
    public function setConfig(int $companyId, array $params): bool
    {
        // Built with ShopEx Framework
        $key = $this->cacheKey($companyId);
        $status = app('redis')->set($key, json_encode($params));
        if (!$status) {
            throw new ResourceException('外部请求参数配置存储异常，请重试。');
        }
        return $status ? true : false;
    }

    /**
     * 获取外部请求参数配置参数.
     *
     * @param int $companyId 账号id
     * @return array 外部请求参数配置参数
     */
    public function getConfig(int $companyId): array
    {
        $key = $this->cacheKey($companyId);
        $result = app('redis')->get($key);
        $result = array_merge($this->emptyConfig(), json_decode($result ?: '{}', true));
        $result['base_uri'] = ($result['base_uri'] ?? '') ?: config('common.external_baseuri');
        return $result;
    }

    /**
     * 获取存储配置key.
     *
     * @param int $companyId 账号id
     * @return string 存储配置key
     */
    public function cacheKey(int $companyId): string
    {
        return "config:account:{$companyId}:external";
    }
}
