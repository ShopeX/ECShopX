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

use Dingo\Api\Exception\ResourceException;

class Config
{
    protected $appId;

    protected $merchantPrivateKey;

    protected $apiSignMethod;

    public const API_SIGN_METHOD_KEY = 'key';
    public const API_SIGN_METHOD_CERT = 'cert';
    public const API_SIGN_METHOD_DEFAULT = self::API_SIGN_METHOD_KEY;
    public const API_SIGN_METHOD_ARRAY = [
        self::API_SIGN_METHOD_KEY => '密钥',
        self::API_SIGN_METHOD_CERT => '证书',
    ];

    protected $alipayCertPath;

    protected $alipayRootCertPath;

    protected $merchantCertPath;

    protected $alipayPublicKey;

    protected $notifyUrl;

    protected $encryptKey;

    /**
     * get AppId
     *
     * @return mixed
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * set AppId
     *
     * @param mixed $appId
     *
     * @return self
     */
    public function setAppId($appId): self
    {
        $this->appId = $appId;
        return $this;
    }

    /**
     * get MerchantPrivateKey
     *
     * @return mixed
     */
    public function getMerchantPrivateKey()
    {
        return $this->merchantPrivateKey;
    }

    /**
     * set MerchantPrivateKey
     *
     * @param mixed $merchantPrivateKey
     *
     * @return self
     */
    public function setMerchantPrivateKey($merchantPrivateKey): self
    {
        $this->merchantPrivateKey = $merchantPrivateKey;
        return $this;
    }

    /**
     * get ApiSignMethod
     *
     * @return mixed
     */
    public function getApiSignMethod()
    {
        if (empty($this->apiSignMethod)) {
            return self::API_SIGN_METHOD_DEFAULT;
        }
        return $this->apiSignMethod;
    }

    /**
     * set ApiSignMethod
     *
     * @param mixed $apiSignMethod
     *
     * @return self
     */
    public function setApiSignMethod($apiSignMethod): self
    {
        if (!in_array($apiSignMethod, array_keys(self::API_SIGN_METHOD_ARRAY))) {
            throw new ResourceException('ApiSignMethod 类型错误');
        }
        $this->apiSignMethod = $apiSignMethod;
        return $this;
    }

    /**
     * get AlipayCertPath
     *
     * @return mixed
     */
    public function getAlipayCertPath()
    {
        return $this->alipayCertPath;
    }

    /**
     * set AlipayCertPath
     *
     * @param mixed $alipayCertPath
     *
     * @return self
     */
    public function setAlipayCertPath($alipayCertPath): self
    {
        $this->alipayCertPath = $alipayCertPath;
        return $this;
    }

    /**
     * get AlipayRootCertPath
     *
     * @return mixed
     */
    public function getAlipayRootCertPath()
    {
        return $this->alipayRootCertPath;
    }

    /**
     * set AlipayRootCertPath
     *
     * @param mixed $alipayRootCertPath
     *
     * @return self
     */
    public function setAlipayRootCertPath($alipayRootCertPath): self
    {
        $this->alipayRootCertPath = $alipayRootCertPath;
        return $this;
    }

    /**
     * get MerchantCertPath
     *
     * @return mixed
     */
    public function getMerchantCertPath()
    {
        return $this->merchantCertPath;
    }

    /**
     * set MerchantCertPath
     *
     * @param mixed $merchantCertPath
     *
     * @return self
     */
    public function setMerchantCertPath($merchantCertPath): self
    {
        $this->merchantCertPath = $merchantCertPath;
        return $this;
    }

    /**
     * get AlipayPublicKey
     *
     * @return mixed
     */
    public function getAlipayPublicKey()
    {
        return $this->alipayPublicKey;
    }

    /**
     * set AlipayPublicKey
     *
     * @param mixed $alipayPublicKey
     *
     * @return self
     */
    public function setAlipayPublicKey($alipayPublicKey): self
    {
        $this->alipayPublicKey = $alipayPublicKey;
        return $this;
    }

    /**
     * get NotifyUrl
     *
     * @return mixed
     */
    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    /**
     * set NotifyUrl
     *
     * @param mixed $notifyUrl
     *
     * @return self
     */
    public function setNotifyUrl($notifyUrl): self
    {
        $this->notifyUrl = $notifyUrl;
        return $this;
    }

    /**
     * get EncryptKey
     *
     * @return mixed
     */
    public function getEncryptKey()
    {
        return $this->encryptKey;
    }

    /**
     * set EncryptKey
     *
     * @param mixed $encryptKey
     *
     * @return self
     */
    public function setEncryptKey($encryptKey): self
    {
        $this->encryptKey = $encryptKey;
        return $this;
    }
}
