<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
