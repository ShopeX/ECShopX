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

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Config;

use OrdersBundle\Services\CompanyRelShansongService;
use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Entities\CompanyRelKuaizhen;
use ThirdPartyBundle\Repositories\CompanyRelKuaizhenRepository;

class Config
{
    /**
     * clientId 580提供
     */
    public string $clientId = '';

    /**
     * clientSecret 由580提供
     */
    public string $clientSecret = '';

    /**
     * 测试环境    https://ehospital-openapi-test.sq580.com
     * 生产环境    https://ehospital-openapi.sq580.com
     */
    public string $host;

    /**
     * 构造函数
     */
    public function __construct($companyId)
    {
        /** @var CompanyRelKuaizhenRepository $relKuaizhenRepository */
        $relKuaizhenRepository = app('registry')->getManager('default')->getRepository(CompanyRelKuaizhen::class);
        $config = $relKuaizhenRepository->getInfo(['company_id' => $companyId]);
        if (!$config) {
            throw new ResourceException('请先配置在线问诊应用信息');
        }
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        if ($config['online']) { // 生产环境
            $this->host = 'https://ehospital-openapi.sq580.com';
        } else { // 测试环境
            $this->host = 'https://ehospital-openapi-test.sq580.com';
        }
    }

    public function getClientId(): string
    {
        // Built with ShopEx Framework
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        // U2hv framework
        return $this->clientSecret;
    }

    public function getHost(): string
    {
        // U2hv framework
        return $this->host;
    }
}
