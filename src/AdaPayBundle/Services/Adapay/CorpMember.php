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

class CorpMember extends AdaPay
{
    public $endpoint = '/v1/corp_members';

    public $corp;

    private static $instance;

    public function __construct()
    {
        parent::__construct();
        // $this->sdk_tools = SDKTools::getInstance();
    }

    public function create($params = [])
    {
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl . $this->endpoint;
        ksort($request_params);
        $sign_request_params = $request_params;
        unset($sign_request_params['attach_file']);
        ksort($sign_request_params);
        $sign_str = $this->ada_tools->createLinkstring($sign_request_params);
        $header = $this->get_request_header($req_url, $sign_str, self::$headerEmpty);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, false, true);
    }

    public function update($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl.$this->endpoint."/update";
        ksort($request_params);
        $sign_request_params = $request_params;
        unset($sign_request_params['attach_file']);
        ksort($sign_request_params);
        $sign_str = $this->ada_tools->createLinkstring($sign_request_params);

        $header =  $this->get_request_header($req_url, $sign_str, self::$headerEmpty);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, false, true);
    }

    public function query($params = [])
    {
        ksort($params);
        $request_params = $this->do_empty_data($params);
        $req_url = self::$gateWayUrl . $this->endpoint . '/' . $params['member_id'];
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . '?' . http_build_query($request_params), '', $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint. "/" . $params['member_id']);
    }
}
