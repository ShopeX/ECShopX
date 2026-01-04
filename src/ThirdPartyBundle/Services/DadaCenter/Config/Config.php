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

namespace ThirdPartyBundle\Services\DadaCenter\Config;

use OrdersBundle\Services\CompanyRelDadaService;

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
     * api版本
     */
    public $v = "1.0";

    /**
     * 数据格式
     */
    public $format = "json";

    /**
     * 商户ID
     */
    public $source_id;

    /**
     * host
     */
    public $host;


    /**
     * 构造函数
     */
    public function __construct($company_id)
    {
        $this->app_key = config('common.dada_app_key');
        $this->app_secret = config('common.dada_app_secret');
        $online = config('common.dada_is_online');
        if ($online) {
            // 根据company_id查询source_id
            $companyRelDadaService = new CompanyRelDadaService();
            $relDadaInfo = $companyRelDadaService->getInfo(['company_id' => $company_id]);
            $source_id = $relDadaInfo['source_id'] ?? '';
            $this->source_id = $source_id;
            $this->host = "https://newopen.imdada.cn";
        } else {
            $this->source_id = "1239307635";
            $this->host = "http://newopen.qa.imdada.cn";
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

    public function getV()
    {
        return $this->v;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getSourceId()
    {
        return $this->source_id;
    }

    public function getHost()
    {
        return $this->host;
    }
}
