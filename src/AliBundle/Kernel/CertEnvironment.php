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

use http\Exception\RuntimeException;

class CertEnvironment extends \Alipay\EasySDK\Kernel\CertEnvironment
{
    protected $rootCertSN;

    protected $merchantCertSN;

    protected $cachedAlipayPublicKey;
    /**
     * 构造证书运行环境
     * @param $merchantCertPath    string 商户公钥证书路径
     * @param $alipayCertPath      string 支付宝公钥证书路径
     * @param $alipayRootCertPath  string 支付宝根证书路径
     */
    public function certEnvironment($merchantCertPath, $alipayCertPath, $alipayRootCertPath)
    {
        if (empty($merchantCertPath) || empty($alipayCertPath) || empty($alipayRootCertPath)) {
            throw new RuntimeException("证书参数merchantCertPath、alipayCertPath或alipayRootCertPath设置不完整。");
        }
        $antCertificationUtil = new AntCertificationUtil();
        $this->rootCertSN = $antCertificationUtil->getRootCertSN($alipayRootCertPath);
        $this->merchantCertSN = $antCertificationUtil->getCertSN($merchantCertPath);
        $this->cachedAlipayPublicKey = $antCertificationUtil->getPublicKey($alipayCertPath);
    }
}
