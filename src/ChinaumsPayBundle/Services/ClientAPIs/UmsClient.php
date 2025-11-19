<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ChinaumsPayBundle\Services\ClientAPIs;
use ChinaumsPayBundle\Services\ClientAPIs\ClientBase;

class UmsClient extends ClientBase
{
    public function __construct()
    {
        $this->config = config('ums');
        $this->uri    = $this->config['uri'];
    }

    public function request(array $params = []):  ? string
    {
    
        $params += $this->config + ['timestamp' => date("YmdHis",time()), 'nonce' => md5(uniqid(microtime(true),true)) ];
        $params['sign'] = $this->authSign($params);

        $appid = $this->config['AppId'];
        $timestamp = $params['timestamp'];
        $nonce = $params['nonce'];
        $signature = $params['sign'];
        $authorization = "OPEN-BODY-SIG AppId=\"$appid\", Timestamp=\"$timestamp\", Nonce=\"$nonce\", Signature=\"$signature\"";

        $this->uri .= $params['url'];
        $params['headers'] = [
            'Content-Length' => strlen(json_encode($params['body'])),
            'Authorization' => $authorization,
        ];
        $this->setOptions($params);
        return $this->call($params['body']);
    }

    public function authSign(array $params = []) :  ? string
    {
        $str = bin2hex( hash('sha256', json_encode($params['body']), true ) );
        $appid = $this->config['AppId'];
        $timestamp = $params['timestamp'];
        $nonce = $params['nonce'];
        return base64_encode( hash_hmac('sha256', "$appid$timestamp$nonce$str", $this->config['AppKey'], true) );
    }


    public function unifiedOrder(array $params) :  ? string
    {
        $postdata['url'] = '/wx/unified-order';
        $postdata['method'] = 'post';
        $postdata['method_type'] = [
            'post'=> 'json'
        ];
        $postdata['body'] = $params;
        return $this->request($postdata);
    }

    public function refund(array $params) :  ? string
    {
        $postdata['url'] = '/refund';
        $postdata['method'] = 'post';
        $postdata['method_type'] = [
            'post'=> 'json'
        ];
        $postdata['body'] = $params;
        return $this->request($postdata);
    }

    public function queryOrder(array $params) :  ? string
    {
        $postdata['url'] = '/query';
        $postdata['method'] = 'post';
        $postdata['method_type'] = [
            'post'=> 'json'
        ];
        $postdata['body'] = $params;
        return $this->request($postdata);
    }

    public function queryRefund(array $params) :  ? string
    {
        $postdata['url'] = '/refund-query';
        $postdata['method'] = 'post';
        $postdata['method_type'] = [
            'post'=> 'json'
        ];
        $postdata['body'] = $params;
        return $this->request($postdata);
    }


    public function genSign(array $params = []) :  ? string
    {
        return strtoupper( hash('sha256', substr( $this->assemble($params),0,-1 ) . $this->config['Md5Key'] ) );
    }

    public static function assemble(array $params = []) :  ? string
    {
        if (!is_array($params)) {
            return null;
        }

        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $sign .= $key .'='. (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value).'&';
            app('log')->info('sign====' . $sign . PHP_EOL);
        }

        return $sign;
    }

}
