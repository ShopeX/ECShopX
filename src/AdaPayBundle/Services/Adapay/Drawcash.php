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

class Drawcash extends AdaPay
{
    public $refundOrder;

    public $refundOrderQuery;

    public $endpoint = '/v1/cashs';

    private static $instance;

    public function __construct()
    {
        // HACK: temporary solution
        parent::__construct();
        // $this->sdk_tools = SDKTools::getInstance();
    }

    //=============取现对象

    /**
     * 创建取现对象
     * @param mixed $params
     */
    public function create($params = [])
    {
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl . $this->endpoint;
        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json = true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint);
    }

    /**
     * 查询取现对象
     * @param mixed $params
     */
    public function query($params = [])
    {
        // HACK: temporary solution
        $request_params = $params;
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl . $this->endpoint . '/stat';
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . '?' . http_build_query($request_params), '', $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/stat");
    }
}
