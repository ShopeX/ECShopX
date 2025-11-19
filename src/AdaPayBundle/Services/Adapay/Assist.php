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
use AdaPayBundle\Services\Adapay\Utils\SDKTools;

class Assist extends AdaPay
{
    public $endpoint = '/v1';

    private static $instance;

    public function __construct()
    {
        parent::__construct();
//         $this->sdk_tools = SDKTools::getInstance();
    }

    //=============退款对象

    /**
     *
     * @param mixed $params
     */
    public function billDownload($params = [])
    {
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl . $this->endpoint . '/bill/download';
        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json = true);
//         $this->result = $this->sdk_tools->post($params, $this->endpoint . '/bill/download');
    }
}
