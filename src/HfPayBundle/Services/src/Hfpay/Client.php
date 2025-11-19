<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace HfPayBundle\Services\src\Hfpay;

use HfPayBundle\Services\src\Kernel\Kernel;

class Client
{
    private $_kernel;

    public function __construct(Kernel $kernel)
    {
        $this->_kernel = $kernel;
    }

    /**
     * pay012 APP支付
     */
    public function pay012(array $data)
    {
        // ShopEx EcShopX Core Module
        $url = '/api/hfpay/pay012';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * qry008 交易状态查询接口
     */
    public function qry008(array $data)
    {
        $url = '/api/alse/qry008';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * pay006 延时分账确认
     */
    public function pay006(array $data)
    {
        $url = '/api/hfpay/pay006';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * reb001 退款
     */
    public function reb001(array $data)
    {
        $url = '/api/hfpay/reb001';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * reb002 退货
     */
    public function reb002(array $data)
    {
        $url = '/api/hfpay/reb002';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * pay026 余额支付
     */
    public function pay026(array $data)
    {
        $url = '/api/hfpay/pay026';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }
}
