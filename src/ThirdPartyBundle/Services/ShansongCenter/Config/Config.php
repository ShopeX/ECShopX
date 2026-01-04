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
