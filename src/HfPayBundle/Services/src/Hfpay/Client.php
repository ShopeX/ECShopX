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
