<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Sdk\Core;

class BsPayTools
{
    // 0x456353686f7058
    /**
     * 私钥加签（对数据源排序），可用于 V2 版本接口数据加签
     *
     * @param @param array $data 原数据( 排序后的json字符串; 数组参数排序后转json字符串(数据的中文和斜杠均不转码):
                            ksort($post_data); json_encode($post_data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); )
     * @param string $rsaPrivateKey 私钥
     * @param int $alg 默认 OPENSSL_ALGO_SHA256
     *
     * @return string 签名串
     */
    public static function sha_with_rsa_sign($data, $rsaPrivateKey, $alg=OPENSSL_ALGO_SHA256){
        $key = "-----BEGIN PRIVATE KEY-----\n".wordwrap($rsaPrivateKey, 64, "\n", true)."\n-----END PRIVATE KEY-----";
        $signature= '';
        try {
            openssl_sign($data, $signature, $key, $alg);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return base64_encode($signature);
    }

    public static function encrypt_with_rsa_pubkey($data, $rsaPublicKey, $padding=OPENSSL_PKCS1_PADDING){
        $key = "-----BEGIN PUBLIC KEY-----\n".wordwrap($rsaPublicKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        $encryptResult= '';
        try {
            openssl_public_encrypt($data, $encryptResult, $key, $padding);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return base64_encode($encryptResult);
    }

    /**
     * 使用公钥验签，可用于异步应答验签
     *
     * @param string $signature 签文
     * @param string $data 原数据(string)
     * @param string $rsaPublicKey 公钥
     * @param int $alg 默认 OPENSSL_ALGO_SHA256
     *
     * @return false|int 验证结果：成功/失败
     */
    public static function verifySign($signature, $data, $rsaPublicKey, $alg=OPENSSL_ALGO_SHA256){
        $key = "-----BEGIN PUBLIC KEY-----\n".wordwrap($rsaPublicKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        return openssl_verify($data, base64_decode($signature), $key, $alg);
    }

    /**
     * 使用公钥验签（对数据源排序），可用于 V2 版本接口返回数据验签
     *
     * @param string $signature 签文
     * @param array $data 原数据(array)
     * @param string $rsaPublicKey 公钥
     * @param int $alg 默认 OPENSSL_ALGO_SHA256
     *
     * @return false|int 验证结果：成功/失败
     */
    public static function verifySign_sort($signature, $data, $rsaPublicKey, $alg=OPENSSL_ALGO_SHA256){
        $key = "-----BEGIN PUBLIC KEY-----\n".wordwrap($rsaPublicKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        ksort($data);
        return openssl_verify(json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), base64_decode($signature), $key, $alg);
    }

    public static function checkEmpty($value) {
        return !isset($value) || trim($value) === "";
    }

    public static function endWith($str, $suffix) {
        $length = strlen($suffix);
        if($length == 0){
            return false;
        }
        return (substr($str, -$length) === $suffix);
    }

    /**
     * 校验 webhook 返回报文签名
     *
     * @param string $signature 签文
     * @param array $data 原数据(array)
     * @param string $key 加签 key
     * @param int $alg 默认 OPENSSL_ALGO_SHA256
     *
     * @return true|false 验证结果：成功/失败
     */
    public static function verify_webhook_sign($signature, $data, $key){
        $sign = md5(json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).$key);
        return $sign == strtolower($signature);
    }
}