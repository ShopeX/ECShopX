<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShansongCenter\Config;

use OrdersBundle\Services\CompanyRelShansongService;
use Dingo\Api\Exception\ResourceException;

class Config
{
    /**
     * 达达开发者app_key
     */
    public $app_key = '';

    /**
     * 达达开发者app_secret
     */
    public $app_secret = '';

    /**
     * 商户ID
     */
    public $shop_id;

    /**
     * host
     */
    public $host;


    /**
     * 构造函数
     */
    public function __construct($company_id)
    {
        // 根据company_id查询shop_id
        $companyRelShansongService = new CompanyRelShansongService();
        $relShansongInfo = $companyRelShansongService->getInfo(['company_id' => $company_id]);
        if (!$relShansongInfo) {
            throw new ResourceException('请先配置闪送应用信息');
        }
        $this->shop_id = $relShansongInfo['shop_id'];
        $this->app_key = $relShansongInfo['client_id'];
        $this->app_secret = $relShansongInfo['app_secret'];
        $online = $relShansongInfo['online'];
        if ($online) {
            $this->host = 'https://open.ishansong.com';
        } else {
            $this->host = 'http://open.s.bingex.com';
        }
    }

    public function getAppKey()
    {
        return $this->app_key;
    }

    public function getAppSecret()
    {
        return $this->app_secret;
    }

    public function getShopId()
    {
        return $this->shop_id;
    }

    public function getHost()
    {
        return $this->host;
    }
}
