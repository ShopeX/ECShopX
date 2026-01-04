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

namespace AliBundle\Kernel;

use Alipay\EasySDK\Kernel\Factory as AliFactory;
use Alipay\EasySDK\Kernel\Config as AliConfig;
use Dingo\Api\Exception\ResourceException;

class Application
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Factory
     */
    protected $factory;

    public function __construct(Config $config)
    {
        $this->setConfig($config);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setConfig(Config $config)
    {
        $this->config = $config;
        $this->factory = AliFactory::setOptions($this->getOptions());
    }

    public function getFactory(): AliFactory
    {
        return $this->factory;
    }

    protected function getOptions(): AliConfig
    {
        $options = new AliConfig();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';

        $options->appId = $this->config->getAppId();

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = $this->config->getMerchantPrivateKey();

        if ($this->config->getApiSignMethod() == Config::API_SIGN_METHOD_CERT) {
            $options->alipayCertPath = $this->config->getAlipayCertPath();
            $options->alipayRootCertPath = $this->config->getAlipayRootCertPath();
            $options->merchantCertPath = $this->config->getMerchantCertPath();
        } elseif ($this->config->getApiSignMethod() == Config::API_SIGN_METHOD_KEY) {
            //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
            $options->alipayPublicKey = $this->config->getAlipayPublicKey();
        } else {
            throw new ResourceException('ApiSignMethod 类型错误');
        }

        //可设置异步通知接收服务地址（可选）
        $options->notifyUrl = $this->config->getNotifyUrl();

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        $options->encryptKey = $this->config->getEncryptKey();

        return $options;
    }

    protected function getOptionsExample(): AliConfig
    {
        $options = new AliConfig();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';

        $options->appId = '<-- 请填写您的AppId，例如：2019022663440152 -->';

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = '<-- 请填写您的应用私钥，例如：MIIEvQIBADANB ... ... -->';

        $options->alipayCertPath = '<-- 请填写您的支付宝公钥证书文件路径，例如：/foo/alipayCertPublicKey_RSA2.crt -->';
        $options->alipayRootCertPath = '<-- 请填写您的支付宝根证书文件路径，例如：/foo/alipayRootCert.crt" -->';
        $options->merchantCertPath = '<-- 请填写您的应用公钥证书文件路径，例如：/foo/appCertPublicKey_2019051064521003.crt -->';

        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        // $options->alipayPublicKey = '<-- 请填写您的支付宝公钥，例如：MIIBIjANBg... -->';

        //可设置异步通知接收服务地址（可选）
        $options->notifyUrl = "<-- 请填写您的支付类接口异步通知接收服务地址，例如：https://www.test.com/callback -->";

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        $options->encryptKey = "<-- 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA== -->";

        return $options;
    }

}
