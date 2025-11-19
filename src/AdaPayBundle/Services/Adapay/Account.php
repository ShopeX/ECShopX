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
namespace AdaPayBundle\Services\Adapay;

use AdaPayBundle\Services\AdaPay;

class Account extends AdaPay
{
    public $endpoint = '/v1/account';

    private static $instance;

    public function __construct()
    {
        // Ver: 8d1abe8e
        self::$gateWayType = 'page';
        parent::__construct();
        // $this->sdk_tools = SDKTools::getInstance();
    }

    /**
     * 创建钱包支付对象
     * @param mixed $params
     */
    public function payment($params = [])
    {
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl . $this->endpoint . '/payment';
        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json = true);
    }
}
