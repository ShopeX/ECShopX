<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ShuyunBundle\Services\Client;

use GuzzleHttp\Client as HttpClient;
use Dingo\Api\Exception\ResourceException;

use CompanysBundle\Entities\Companys;

use ShuyunBundle\Services\Config\Config;
use ShuyunBundle\Services\Config\Constant;

class Request
{
    /**
     * http request timeout;
     */
    private $httpTimeout = 5;

    /**
     * 配置项
     */
    private $config;

    private $shopexUid = "";
    private $userId = "";

    /**
     * 构造函数
     */
    public function __construct($companyId = null, $userId = null)
    {
        $this->config = new Config();
        if ($companyId) {
            $companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
            $company = $companysRepository->get(['company_id' => $companyId]);
            $this->shopexUid = $company->getPassportUid();
        }
        $this->userId = $userId;
    }

    public function get($url, array $options = [])
    {
        if (!empty($this->shopexUid)) {
            $options['shopId'] = $this->shopexUid;
        }
        if (!empty($this->userId)) {
            $options['platAccount'] = $this->userId;
        }
        if (!empty($options['operator'])) {
            $options['operator'] = $this->shopexUid ?? '';
        }
        $queries = $this->getSignatureArray($options);
        return $this->request($url, 'GET', ['query' => $queries, 'headers' => ['content-type' => 'application/json']]);
    }

    /**
     * @param string $url
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function json($url, $options = [])
    {
        if (!empty($this->shopexUid)) {
            $options['shopId'] = $this->shopexUid;
        }
        if (!empty($this->userId)) {
            $options['platAccount'] = $this->userId;
        }
        if (!empty($options['operator'])) {
            $options['operator'] = $this->shopexUid ?? '';
        }
        $queries = $this->getSignatureArray();
        is_array($options) && $options = json_encode($options, JSON_UNESCAPED_UNICODE);
        return $this->request($url, 'POST', ['query' => $queries, 'body' => $options, 'headers' => ['content-type' => 'application/json']]);
    }

    private function request($url, $method = 'GET', $options = [])
    {
        app('log')->info('ShuyunRequest api:'.$url.',method:'.$method.',options:'.json_encode($options));
        try {
            $url = $this->config->host.$url;
            $config['base_uri'] = $this->config->host;
            $client = new HttpClient($config);
            $reponse = $client->request($method, $url, $options);
            $reponse = $reponse->getBody()->getContents();
            app('log')->info('ShuyunRequest reponse url:'.$url.',method:'.$method.',options:'.json_encode($options).',reponse:'.var_export($reponse, true));
            return $this->parseResponseData($reponse);
        } catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
            ];
            app('log')->info('ShuyunRequest Error url:'.$url.',method:'.$method.',options:'.json_encode($options).',error:'.var_export($error, true));
            return $this->parseResponseData([]);
        }
    }

    /**
     * @return array
     *
     * 获取签名信息
     */
    public function getSignatureArray($requestParams = [])
    {
        $config = $this->getConfig();

        $requestParams['u_appId'] = $config->u_appId;
        $requestParams['u_sign_method'] = $config->u_sign_method;
        $requestParams['u_timestamp'] = round(microtime(true) * 1000);
        $requestParams['u_signature'] = $this->_sign($requestParams);
        
        return $requestParams;
    }

    /**
     * 签名生成signature
     */
    public function _sign($data)
    {
        $config = $this->getConfig();

        //1.升序排序
        ksort($data);

        //2.字符串拼接
        $args = "";
        foreach ($data as $key => $value) {
            $args .= $key . $value;
        }
        $args = $config->u_appsecret . $args . $config->u_appsecret;
        //3.MD5签名
        $sign = md5($args);
        return $sign;
    }

    /**
     * 解析响应数据
     * @param $arr返回的数据
     * 响应数据格式：{"data":{},"code":0,"message":""}
     */
    public function parseResponseData($arr)
    {
        $resp = new Response();
        if (empty($arr)) {
            $resp->setCode(Constant::FAIL_CODE);
            $resp->setMessage(Constant::FAIL_MSG);
        } else {
            $data = json_decode($arr, true);
            $resp->setCode($data['code'] ?? '');
            $resp->setMessage($data['message']);
            $resp->setData($data['data'] ?? false);
        }
        return $resp;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
