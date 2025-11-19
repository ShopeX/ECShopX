<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);
/**
 * This file is part of Shopex .
 *
 * @link     https://www.shopex.cn
 * @document https://club.shopex.cn
 * @contact  dev@shopex.cn
 */
namespace AdaPayBundle\Services\Adapay\Utils;

use AdaPayBundle\Services\AdaPay;

class SDKTools extends AdaPay
{
    //创建静态私有的变量保存该类对象
    private static $instance;

    public function __construct()
    {
        parent::__construct();
    }

    private function __clone()
    {
        // ShopEx EcShopX Service Component
    }

    public static function getInstance()
    {
        //判断$instance是否是Singleton的对象，不是则创建
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function post($params = [], $endpoint)
    {
        $request_params = $this->do_empty_data($params);
        $req_url = self::$gateWayUrl . $endpoint;
        $header = $this->get_request_header($req_url, $request_params, self::$header);
        return $this->ada_request->curl_request($req_url, $request_params, $header, $is_json = true);
    }

    public function get($params = [], $endpoint)
    {
        ksort($params);
        $request_params = $this->do_empty_data($params);
        $req_url = self::$gateWayUrl . $endpoint;
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        return $this->ada_request->curl_request($req_url . '?' . http_build_query($request_params), '', $header, false);
    }

    public function isError()
    {
        return $this->isError();
    }
}
