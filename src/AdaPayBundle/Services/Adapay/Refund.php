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

class Refund extends AdaPay
{
    public $endpoint = '/v1/payments';

    private static $instance;

    public function __construct()
    {
        parent::__construct();
        // $this->sdk_tools = SDKTools::getInstance();
    }

    //=============退款对象

    /**
     * 创建退款对象
     * @param mixed $params
     */
    public function create($params = [])
    {
        $request_params = $params;
        $charge_id = isset($params['payment_id']) ? $params['payment_id'] : '';
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl . $this->endpoint . '/' . $charge_id . '/refunds';
        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json = true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint."/". $charge_id. "/refunds");
    }

    /**
     * 查询退款对象
     * @param mixed $params
     */
    public function query($params = [])
    {
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl . $this->endpoint . '/refunds';
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . '?' . http_build_query($request_params), '', $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/refunds");
    }
}
