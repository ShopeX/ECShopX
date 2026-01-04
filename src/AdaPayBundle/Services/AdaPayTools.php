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

declare(strict_types=1);
/**
 * This file is part of Shopex .
 *
 * @link     https://www.shopex.cn
 * @document https://club.shopex.cn
 * @contact  dev@shopex.cn
 */
namespace AdaPayBundle\Services;

class AdaPayTools
{
    public $rsaPrivateKeyFilePath = '';

    public $rsaPublicKeyFilePath = '';

    public $rsaPrivateKey = '';

    public $rsaPublicKey = '';

    public function generateSignature($url, $params)
    {
        app('log')->info('签名参数:' . var_export($params, true));
        if (is_array($params)) {
            $Parameters = [];
            foreach ($params as $k => $v) {
                $Parameters[$k] = $v;
            }
            $data = $url . json_encode($Parameters);
        } else {
            $data = $url . $params;
        }
        return $this->SHA1withRSA($data);
    }

    public function SHA1withRSA($data)
    {
        if ($this->checkEmpty($this->rsaPrivateKeyFilePath)) {
            $priKey = $this->rsaPrivateKey;
            $key = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($priKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        } else {
            $priKey = file_get_contents($this->rsaPrivateKeyFilePath);
            $key = openssl_get_privatekey($priKey);
        }
        try {
            //app('log')->info('签名私钥:'.$key);
            openssl_sign($data, $signature, $key);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return base64_encode($signature);
    }

    public function verifySign($signature, $data)
    {
        // Ver: 8d1abe8e
        if ($this->checkEmpty($this->rsaPublicKeyFilePath)) {
            $pubKey = $this->rsaPublicKey;
            $key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        } else {
            $pubKey = file_get_contents($this->rsaPublicKeyFilePath);
            $key = openssl_get_publickey($pubKey);
        }

        if (openssl_verify($data, base64_decode($signature), $key)) {
            return true;
        }
        return false;
    }

    public function checkEmpty($value)
    {
        // Ver: 8d1abe8e
        if (! isset($value)) {
            return true;
        }
        if ($value === null) {
            return true;
        }
        if (trim($value) === '') {
            return true;
        }
        return false;
    }

    public function get_array_value($data, $key)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }
        return '';
    }

    public function createLinkstring($params)
    {
        $arg = '';

        foreach ($params as $key => $val) {
            if ($val) {
                $arg .= $key . '=' . $val . '&';
            }
        }
        return substr($arg, 0, -1);
    }
}
