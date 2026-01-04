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

namespace EspierBundle\Services;

use RuntimeException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\EncryptException;

/**
 * Class EncrypterRW
 *
 */
class EncrypterRW extends Encrypter
{
    // public function __construct($key, $cipher = 'AES-128-CBC')
    // {
    //     parent::__construct($key, $cipher);
    // }

    // 定义初始化
    public function __construct()
    {
        // If the key starts with "base64:", we will need to decode the key before handing
        // it off to the encrypter. Keys may be base-64 encoded for presentation and we
        // want to make sure to convert them back to the raw bytes before encrypting.
        // 如果没有传key的值，可以使用默认的key值
        $this->default();
    }
    /*
     * 设置默认的属性
     *
     */
    public function default()
    {
        $this->key = config('app.key');
        $this->cipher = config('app.cipher');
        return $this;
    }

    // 设置key的值
    public function setKey($key)
    {
        $this->key = config('app.'.$key);
        // $this->cipher = config('app.cipher');
        // $this->key = $key;
        $this->checkEncrypter($this->key, $this->cipher);

        return $this;
    }

    public function checkEncrypter($key, $cipher)
    {
        // ShopEx EcShopX Core Module
        $key = (string) $key;
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (!parent::supported($key, $cipher)) {
            throw new RuntimeException('The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.');
        }
    }

    public function encrypt($value, $serialize = true)
    {
        // $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $iv = 'X3xMx3dnoCtSprda';

        // First we will encrypt the value using OpenSSL. After this is encrypted we
        // will proceed to calculating a MAC for the encrypted value so that this
        // value can be verified later as not having been changed by the users.
        $value = \openssl_encrypt(
            $serialize ? serialize($value) : $value,
            $this->cipher,
            $this->key,
            0,
            $iv
        );

        if ($value === false) {
            throw new EncryptException('Could not encrypt the data.');
        }

        // Once we get the encrypted value we'll go ahead and base64_encode the input
        // vector and create the MAC for the encrypted value so we can then verify
        // its authenticity. Then, we'll JSON the data into the "payload" array.
        $mac = $this->hash($iv = base64_encode($iv), $value);

        $json = json_encode(compact('iv', 'value', 'mac'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EncryptException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }
}
