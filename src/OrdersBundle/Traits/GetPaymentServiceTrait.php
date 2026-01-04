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

namespace OrdersBundle\Traits;

use Exception;

use PaymentBundle\Services\Payments\AdaPaymentService;
use PaymentBundle\Services\Payments\AlipayH5Service;
use PaymentBundle\Services\Payments\AlipayAppService;
use PaymentBundle\Services\Payments\AlipayService;
use PaymentBundle\Services\Payments\AlipayPosService;
use PaymentBundle\Services\Payments\DepositPayService;
use PaymentBundle\Services\Payments\HfPayService;
use PaymentBundle\Services\Payments\OfflinePayService;
use PaymentBundle\Services\Payments\WechatH5PayService;
use PaymentBundle\Services\Payments\WechatJSPayService;
use PaymentBundle\Services\Payments\WechatAppPayService;
use PaymentBundle\Services\Payments\WechatPayService;
use PaymentBundle\Services\Payments\PointPayService;
use PaymentBundle\Services\Payments\WechatWebPayService;
use PaymentBundle\Services\Payments\WechatPosPayService;
use PaymentBundle\Services\Payments\PosPayService;
use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use PaymentBundle\Services\Payments\AlipayMiniService;
use PaymentBundle\Services\Payments\BsPayService;
use PaymentBundle\Services\Payments\PaypalService;

trait GetPaymentServiceTrait
{
    /**
     * 小程序支付方式
     *
     * @param string $payType
     */
    public function getPaymentService($payType, $distributorId = 0)
    {
        $payType = strtolower($payType);
        switch ($payType) {
            case 'wxpay':
                $service = new PaymentsService(new WechatPayService($distributorId));
                break;
            case 'wxpaypc':
                $service = new PaymentsService(new WechatWebPayService($distributorId));
                break;
            case 'alipay':
                $service = new PaymentsService(new AlipayService($distributorId));
                break;
            case 'point':
                $service = new PaymentsService(new PointPayService());
                break;
            case 'deposit':
                $service = new PaymentsService(new DepositPayService());
                break;
            case 'alipayh5':
                $service = new PaymentsService(new AlipayH5Service($distributorId));
                break;
            case 'alipayapp':
                $service = new PaymentsService(new AlipayAppService($distributorId));
                break;
            case 'wxpayh5':
                $service = new PaymentsService(new WechatH5PayService($distributorId));
                break;
            case 'wxpayjs':
                $service = new PaymentsService(new WechatJSPayService($distributorId));
                break;
            case 'wxpayapp':
                $service = new PaymentsService(new WechatAppPayService($distributorId));
                break;
            case 'alipaypos':
                $service = new PaymentsService(new AlipayPosService($distributorId));
                break;
            case 'wxpaypos':
                $service = new PaymentsService(new WechatPosPayService($distributorId));
                break;
            case 'hfpay':
                $service = new PaymentsService(new HfPayService());
                break;
            case 'adapay':
                $service = new PaymentsService(new AdaPaymentService());
                break;
            case 'chinaums':
                $service = new PaymentsService(new ChinaumsPayService());
                break;
            case 'pos':
                $service = new PaymentsService(new PosPayService());
                break;
            case 'alipaymini':
                $service = new PaymentsService(new AlipayMiniService($distributorId));
                break;
            case 'bspay':
                $service = new PaymentsService(new BsPayService());
                break;
            case 'offline_pay':
                $service = new PaymentsService(new OfflinePayService());
                break;
            case 'paypal':
                $service = new PaymentsService(new PaypalService($distributorId));
                break;
            default:
                throw new Exception("无此类型支付！");
        }

        return $service;
    }

    /**
     * 导购端支付方式
     *
     * @param string $payType
     */
    public function getGuidePaymentService($payType, $distributorId = 0)
    {
        $payType = strtolower($payType);
        switch ($payType) {
            case 'wxpay':
                $service = new PaymentsService(new WechatPayService($distributorId));
                break;
            case 'wxpaypc':
                $service = new PaymentsService(new WechatWebPayService($distributorId));
                break;
            case 'alipay':
                $service = new PaymentsService(new AlipayService($distributorId));
                break;
            case 'alipayh5':
                $service = new PaymentsService(new AlipayH5Service($distributorId));
                break;
            case 'wxpayh5':
                $service = new PaymentsService(new WechatH5PayService($distributorId));
                break;
            case 'wxpayjs':
                $service = new PaymentsService(new WechatJSPayService($distributorId));
                break;
            case 'pos':
                $service = new PaymentsService(new PosPayService());
                break;
            default:
                throw new Exception("无此类型支付！");
        }

        return $service;
    }

    /**
     * 充值支付方式
     */
    public function getDepositPaymentService($payType)
    {
        $payType = strtolower($payType);
        switch ($payType) {
            case 'alipay':
                $service = new PaymentsService(new AlipayService());
                break;
            case 'alipayh5':
                $service = new PaymentsService(new AlipayH5Service());
                break;
            case 'alipayapp':
                $service = new PaymentsService(new AlipayAppService());
                break;
            case 'wxpay':
                $service = new PaymentsService(new WechatPayService());
                break;
            case 'wxpayh5':
                $service = new PaymentsService(new WechatH5PayService());
                break;
            case 'wxpayjs':
                $service = new PaymentsService(new WechatJSPayService());
                break;
            case 'wxpayapp':
                $service = new PaymentsService(new WechatAppPayService());
                break;
            case 'adapay':
                $service = new PaymentsService(new AdaPaymentService());
                break;
            case 'paypal':
                $service = new PaymentsService(new PaypalService());
                break;
            default:
                throw new Exception("无此类型支付！");
        }

        return $service;
    }
}
