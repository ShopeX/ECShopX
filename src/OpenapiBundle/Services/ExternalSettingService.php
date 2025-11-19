<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
