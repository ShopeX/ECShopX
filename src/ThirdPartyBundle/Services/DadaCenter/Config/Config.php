<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
