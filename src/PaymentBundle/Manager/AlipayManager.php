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

namespace PaymentBundle\Manager;

use Yansongda\Pay\Pay;
use Dingo\Api\Exception\ResourceException;

class AlipayManager
{
    private $config = [];

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    /**
     * 支付实例
     */
    public function payment($config, $returnUrl = '')
    {
        $payConfig = $this->config;
        if (!($config['app_id'] ?? 0) || empty($config['app_id'])) {
            throw new ResourceException('支付宝支付未配置');
        }
        $payConfig['app_id'] = $config['app_id'];
        $payConfig['ali_public_key'] = $config['ali_public_key'];
        $payConfig['private_key'] = $config['private_key'];
        $payConfig['return_url'] = $returnUrl ?: $payConfig['return_url_pc'];
        return Pay::alipay($payConfig);
    }

    /**
     * H5支付实例
     */
    public function paymentH5($config, $returnUrl = '')
    {
        $payConfig = $this->config;
        if (!($config['app_id'] ?? 0) || empty($config['app_id'])) {
            throw new ResourceException('支付宝支付未配置');
        }
        $payConfig['app_id'] = $config['app_id'];
        $payConfig['ali_public_key'] = $config['ali_public_key'];
        $payConfig['private_key'] = $config['private_key'];
        $payConfig['return_url'] = $returnUrl ?: $payConfig['return_url_h5'];
        return Pay::alipay($payConfig);
    }

    /**
     * App支付实例
     */
    public function paymentApp($config, $returnUrl = '')
    {
        $payConfig = $this->config;
        if (!($config['app_id'] ?? 0) || empty($config['app_id'])) {
            throw new ResourceException('支付宝支付未配置');
        }
        $payConfig['app_id'] = $config['app_id'];
        $payConfig['ali_public_key'] = $config['ali_public_key'];
        $payConfig['private_key'] = $config['private_key'];
        $payConfig['return_url'] = $returnUrl ?: $payConfig['return_url_app'];
        return Pay::alipay($payConfig);
    }

    /**
     * POS支付实例
     */
    public function paymentPos($config, $returnUrl = '')
    {
        $payConfig = $this->config;
        if (!($config['app_id'] ?? 0) || empty($config['app_id'])) {
            throw new ResourceException('支付宝支付未配置');
        }
        $payConfig['app_id'] = $config['app_id'];
        $payConfig['ali_public_key'] = $config['ali_public_key'];
        $payConfig['private_key'] = $config['private_key'];
        $payConfig['return_url'] = $returnUrl ?: $payConfig['return_url_pos'];
        return Pay::alipay($payConfig);
    }

    public function paymentMini($config, $returnUrl = '', $app_auth_token = '')
    {
        $payConfig = $this->config;
        if (!($config['app_id'] ?? 0) || empty($config['app_id'])) {
            throw new ResourceException('支付宝支付未配置');
        }
        $payConfig['app_id'] = $config['app_id'];
        $payConfig['ali_public_key'] = $config['ali_public_key'];
        $payConfig['private_key'] = $config['private_key'];
        $payConfig['return_url'] = $returnUrl ?: $payConfig['return_url_app'];
        $payConfig['app_auth_token'] = $app_auth_token;
        return Pay::alipay($payConfig);
    }
}
