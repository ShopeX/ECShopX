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

namespace SystemLinkBundle\Services\WdtErp\Client;

class WdtErpClient
{
    private $url = '';
    private $sid = '';
    private $key = '';
    private $secret = '';
    private $salt = '';
    private $version = '1.0';
    private $multi_tenant_mode = false;

    public function __construct($url, $sid, $appkey, $secret)
    {
        $this->url = $url;
        $this->sid = $sid;
        $this->key = $appkey;
        $arr = explode(':', $secret, 2);
        $this->secret = $arr[0];
        $this->salt = $arr[1];
    }

    private function makeSign(&$req)
    {
        // Built with ShopEx Framework
        ksort($req);

        $arr = array();
        $arr[] = $this->secret;
        foreach($req as $key => $val)
        {
            if($key == 'sign') continue;

            $arr[] = $key;
            $arr[] = $val;
        }
        $arr[] = $this->secret;

        $sign = md5(implode('', $arr));
        $req['sign'] = $sign;
    }

    private function execute($method, $pager, $args)
    {
        list($body, $service_url) = $this->buildRequest($method, $pager, $args);
        $response = $this->sendRequest($body, $service_url);
        $json = "";
        if ($this->isJson($response))
            $json = json_decode($response);
        else
            $json = $this->sendRequest($body, $service_url);

        if(isset($json->status) && $json->status>0)
        {
            throw new WdtErpException($json->message, $json->status);
        }

        return $json;
    }

    /**
     * build request
     * @param $method
     * @param $pager
     * @param $args
     * @return array
     */
    public function buildRequest($method, $pager, $args)
    {
        $req = array();
        $req['sid'] = $this->sid;
        $req['key'] = $this->key;
        $req['salt'] = $this->salt;
        $req['method'] = $method;
        $req['timestamp'] = time() - 1325347200;
        $req['v'] = $this->version;

        if ($pager != NULL) {
            $req['page_size'] = $pager->getPageSize();
            $req['page_no'] = $pager->getPageNo();
            $req['calc_total'] = $pager->getCalcTotal() ? 1 : 0;
        }

        $body = json_encode($args);
        $req['body'] = $body;

        $this->makeSign($req, $this->secret);

        unset($req['body']);

        $service_url = $this->url . '?' . http_build_query($req);
        return array($body, $service_url);
    }

    /**
     * send http request
     * @param $body
     * @param $service_url
     * @return false|string
     */
    public function sendRequest($body, $service_url)
    {
        $header_connection = $this->multi_tenant_mode ? "Connection:close" : "";
        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n"
                    . "X-Version-SDK: php-3.0"
                    . $header_connection,
                'content' => $body
            )
        );

        $context = stream_context_create($opts);

        return file_get_contents($service_url, false, $context);
    }

    /**
     * check if a string is json
     * @param $string
     * @return bool
     */
    function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function cacheGet($key, $secs)
    {
        $g_cache_dir = './tmp/';

        $path = $g_cache_dir . md5($key);

        $str = @file_get_contents($path);
        if (empty($str)) return NULL;

        $obj = unserialize($str);
        if (!$obj) return NULL;

        $now = time();
        if ($now - $obj['time'] > $secs) {
            @unlink($path);
            return NULL;
        }

        return $obj['val'];
    }

    private function cachePut($key, $val)
    {
        $g_cache_dir = './tmp/';

        if (!is_dir($g_cache_dir)) {
            @mkdir($g_cache_dir, 0777, true);
        }

        $path = $g_cache_dir . md5($key);

        $obj = array(
            'time' => time(),
            'val' => $val
        );

        file_put_contents($path, serialize($obj));
    }

    public function call($method)
    {
        $args = func_get_args();
        array_shift($args);
        $json = $this->execute($method, NULL, $args);

        return @$json->data;
    }

    public function pageCall($method, $pager)
    {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);

        $json = $this->execute($method, $pager, $args);

        if(!$pager->getCalcTotal())
            return $json->data;

        return $json;
    }

    /*
        调用BeanShell脚本接口
    */
    public function callEx($method)
    {
        $args = func_get_args();
        $json = $this->execute('system.ScriptExtension.call', NULL, $args);

        return @$json->data;
    }
}
