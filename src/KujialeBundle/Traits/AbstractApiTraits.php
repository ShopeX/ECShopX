<?php

namespace KujialeBundle\Traits;

use GuzzleHttp\Client;

trait AbstractApiTraits
{
    private $apiUrl;

    private $apiParams = [];

    private $appKey;

    private $appSecret;

    private $appUid;

    private $client;

    public function __construct()
    {
        $this->setAppKey();
        $this->setAppSecret();
        $this->setAppUid();
        $this->client = new Client();
    }

    public function setApiParams($params)
    {
        $this->apiParams = $params;
    }

    public function getApiParams()
    {
        return $this->apiParams;
    }

    public function setAppKey()
    {
        $config = $this->getKujialeConfig();
        $this->appKey = $config['appKey'] ?? '';
    }

    public function getAppKey()
    {
        return $this->appKey;
    }

    public function setAppSecret()
    {
        $config = $this->getKujialeConfig();
        $this->appSecret = $config['appSecret'] ?? '';
    }

    public function getAppSecret()
    {
        return $this->appSecret;
    }

    public function setAppUid($appUid='')
    {
        if ($appUid != '') {
            $this->appUid = $appUid;
        } else {
            $this->appUid = config('kujiale.appUid');
        }
    }

    public function getAppUid()
    {
        return $this->appUid;
    }

    public function setApiUrl($params)
    {
        $this->apiUrl= $params;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**获取签名
     * @param $appUid
     * @return string
     * @throws \ExceptioTraitsn
     */
    private function genSign($appUid)
    {
        if (empty($this->getAppKey()) || empty($this->getAppSecret()))
        {
            throw  new \Exception('未配置相应的appKey或appSecret');
        }
        if ($appUid)
        {
            if (empty($this->appUid))
            {
                throw  new \Exception('未配置相应的appKey或appSecret');
            }
            return md5($this->getAppSecret().$this->getAppKey().$this->getAppUid().(time()*1000));
        }

        return md5($this->getAppSecret().$this->getAppKey().time()*1000);
    }

    /**post方法获取接口数据
     * @param bool $appUid
     * @return bool
     * @throws \Exception
     */
    public function post_new($postParams, $appUid = false)
    {
        if (!$this->canCallApi($appUid)) {
            return [];
        }
        $postParams = array_merge($postParams, [
            'appkey' => $this->getAppKey(),
            'timestamp' => time()*1000,
        ]);
        $bodyParams = $this->getApiParams();
        if ($appUid){
            $postParams['appuid'] = $this->getAppUid();
            $postParams['sign'] = $this->genSign(true);
        }else{
            $postParams['sign'] = $this->genSign(false);
        }
        $url  = $this->getApiUrl().'?'.http_build_query($postParams);
        //app('log')->info('post请求'.$url.'的参数是'.var_export($bodyParams,1));
        $response = $this->client->request('POST',$url,['headers' => ['Content-Type'=>'application/json;charset=utf-8'],'json'=>$bodyParams]);
        $res = json_decode($response->getBody(),1);
        //app('log')->info('post请求'.$url.'的返回结果是'.var_export($res,1));
        return $res;
    }

    /**post方法获取接口数据
     * @param bool $appUid
     * @return bool
     * @throws \Exception
     */
    public function post($appUid = false)
    {
        if (!$this->canCallApi($appUid)) {
            return false;
        }
        $postParams = [
            'appkey' => $this->getAppKey(),
            'timestamp' => time()*1000,
        ];
        if ($appUid){
            $postParams['appuid'] = $this->getAppUid();
            $postParams['sign'] = $this->genSign(true);
        }else{
            $postParams['sign'] = $this->genSign(false);
        }
        $url  = $this->getApiUrl().'?'.http_build_query($postParams);
        //app('log')->info('post请求'.$url.'的参数是'.var_export($this->getApiParams(),1));
        $response = $this->client->request('POST',$url,['headers' => ['Content-Type'=>'application/json;charset=utf-8'],'json'=>$this->getApiParams()]);
        $res = json_decode($response->getBody(),1);
        //app('log')->info('post请求'.$url.'的返回结果是'.var_export($res,1));
        if ($res['c'] == 0)
        {
            return $res['d'];
        }
        return false;
    }

    /**
     * get方法获取接口数据
     * @param bool $appUid
     * @return mixed
     * @throws \Exception
     */
    public function get($appUid = false)
    {
        if (!$this->canCallApi($appUid)) {
            return false;
        }
        $postParams = [
            'appkey' => $this->getAppKey(),
            'timestamp' => time()*1000,
        ];
        if($appUid) {
            $postParams['appuid'] = $this->getAppUid();
            $postParams['sign'] = $this->genSign(true);
        }else{
            $postParams['sign'] = $this->genSign(false);
        }
        $fromParams = array_merge($postParams,$this->getApiParams());
        $url = $this->getApiUrl().'?'.http_build_query($fromParams);
        //app('log')->info('get请求'.$url.'的参数是'.var_export($fromParams,1));
        $response = $this->client->request('GET',$url,['headers' => ['Content-Type'=>'text/plain;charset=utf-8']]);
        $res = json_decode($response->getBody(),1);
        //app('log')->info('get请求'.$url.'的返回结果是'.var_export($res,1));
        if ($res['c'] == 0)
        {
           return  $res['d'];
        }
        return false;
    }

    private function canCallApi(bool $requireAppUid = false): bool
    {
        if (empty($this->getAppKey()) || empty($this->getAppSecret())) {
            return false;
        }
        if ($requireAppUid && empty($this->getAppUid())) {
            return false;
        }
        return true;
    }

    private function getKujialeConfig(): array
    {
        $redis = app('redis')->connection('default');
        $keys = $redis->keys('kujiale:config:*');
        if (empty($keys)) {
            return [];
        }
        $raw = $redis->get($keys[0]);
        return $raw ? json_decode($raw, true) : [];
    }
}



