<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
