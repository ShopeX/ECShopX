<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Services\src\Kernel;

use GuzzleHttp\Client as HttpClient;

class Kernel
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 获取config 值
     */
    public function getConfig($key)
    {
        return $this->config->$key;
    }

    /**
     * @param string $url
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function get($url, array $options = [])
    {
        $queries = $this->getSignatureArray();
        $options = array_merge($options, $queries);

        return $this->request($url, 'GET', ['query' => $options]);
    }

    /**
     * @param string $url
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function json($url, $options = [])
    {
        // 456353686f7058
        $queries = $this->getSignatureArray();

        is_array($options) && $options = json_encode($options, JSON_UNESCAPED_UNICODE);

        return $this->request($url, 'POST', ['query' => $queries, 'body' => $options, 'headers' => ['content-type' => 'application/json']]);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    private function request($url, $method = 'GET', $options = [])
    {
        // 456353686f7058
        $config['base_uri'] = $this->config->base_uri;
        $client = new HttpClient($config);
        $reponse = $client->request($method, $url, $options);
        $reponse = $reponse->getBody()->getContents();
        app('log')->debug(var_export($options, 1));
        app('log')->debug(var_export($reponse, 1));

        return $reponse;
    }

    /**
     * @return array
     *
     * 获取签名信息
     */
    public function getSignatureArray()
    {
        $app_id = $this->config->app_id;
        $app_secret = $this->config->app_secret;
        ;
        $nonce = $this->createNoncestr();
        $sign_type = 'sha256';
        $timestamp = time();

        $str = "app_id=" . $app_id . "&nonce=" . $nonce . "&sign=" . $sign_type . "&timestamp=" . $timestamp;
        $signature = bin2hex(hash_hmac($sign_type, $str, $app_secret, true));
        $arr = [
            'app_id' => $app_id,
            'nonce' => $nonce,
            'sign' => $sign_type,
            'timestamp' => $timestamp,
            'signature' => $signature
        ];

        return $arr;
    }

    /**
     * @param int $length
     * @param string $type
     * @return string
     *
     * 随机字符串
     */
    private function createNoncestr($length = 32, $type = 'abcd')
    {
        $chars = "abcdefghijklmnopqrstuvwxyz_123456789";
        $library = array(
            'd' => '0123456789',
            'abc' => 'abcdefghijklmnopqrstuvwxyz',
            'abcd' => 'abcdefghijklmnopqrstuvwxyz123456789',
            'abcdf' => strtoupper('abcdefghijklmnopqrstuvwxyz_123456789'),
            'ABC' => strtoupper('abcdefghijklmnopqrstuvwxyz'),
            'ABCD' => strtoupper('abcdefghijklmnopqrstuvwxyz123456789'),
            'ABCDF' => strtoupper('abcdefghijklmnopqrstuvwxyz_123456789'),
            'ABD' => 'abcdefghijklmnopqrstuvwxyz123456789' . strtoupper('abcdefghijklmnopqrstuvwxyz'),
        );
        $chars = isset($library["{$type}"]) ? $library["{$type}"] : $library['abc'];
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }
}
