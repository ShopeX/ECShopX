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

namespace HfPayBundle\Services\src\Kernel;

use Dingo\Api\Exception\ResourceException;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;

class Kernel
{
    private $config;

    public function __construct($config)
    {
        // Log: 456353686f7058
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
     * @param $url
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function get($url, array $options = [])
    {
        return $this->request($url, 'GET', ['query' => $options]);
    }

    /**
     * @param $url
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function post($url, $data = [])
    {
        //加签
        $strSignSourceData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $cfcaSign = $this->signData($strSignSourceData);

        $options = [
            'mer_cust_id' => $data['mer_cust_id'],
            'version' => $data['version'],
            'check_value' => $cfcaSign,
        ];

        $key = is_array($options) ? 'form_params' : 'body';
        app('log')->debug('hf_request_data =>'.var_export($data, 1));
        app('log')->debug('hf_request_params =>'.var_export($options, 1));
        return $this->request($url, 'POST', [$key => $options, 'headers' => ['content-type' => 'application/x-www-form-urlencoded;charset=UTF-8']]);
    }

    /**
     * @param $url
     * @param array $data
     * @param $files
     * @return \Psr\Http\Message\ResponseInterface|string
     *
     * 文件上传
     */
    public function upload($url, $data = [], $files)
    {
        //加签
        $strSignSourceData = json_encode($data);
        $cfcaSign = $this->signData($strSignSourceData);

        // $files->getClientOriginalName();
        $clientMimeType = $files->getClientOriginalExtension();
        $filename = time().'.'.$clientMimeType;
        $multipart = [
            [
                'name' => 'mer_cust_id',
                'contents' => $data['mer_cust_id'],
            ],
            [
                'name' => 'version',
                'contents' => $data['version'],
            ],
            [
                'name' => 'check_value',
                'contents' => $cfcaSign,
            ],
            [
                'name' => 'attach_file',
                'contents' => fopen($files, 'r'),
                'filename' => $filename,
            ]
        ];
        app('log')->debug('hf_upload_data =>'.var_export($multipart, 1));
        return $this->request($url, 'POST', ['multipart' => $multipart]);
    }

    /**
     * @param $url
     * @param string $method
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    private function request($url, $method = 'GET', $options = [])
    {
        $config['base_uri'] = $this->config->base_uri;
        $client = new HttpClient($config);
        $reponse = $client->request($method, $url, $options);
        if ($reponse instanceof ResponseInterface) {
            $reponse = $reponse->getBody()->getContents();
        }

        $checkValue = json_decode($reponse, true)['check_value'];
        $signSourceData = $this->sourceData($checkValue);

        return $signSourceData;
    }

    /**
     * @param $data
     * @return string
     * 数据生成签名
     */
    private function signData($data)
    {
        try {
            $sign = new HfSign();
            $sign->strPfxPassword = $this->config->pfx_password;
            $sign->strPfxFilePath = storage_path('chinapnrPayment/' . $this->config->mer_cust_id . '/cfca.pfx');
            $sign->strTrustedCACertFilePath = storage_path('chinapnrPayment/' . $this->config->mer_cust_id . '/CFCA_ACS_CA.cer') . '|' . storage_path('chinapnrPayment/' . $this->config->mer_cust_id . '/CFCA_ACS_OCA31.cer');
            $sign->strLogCofigFilePath = storage_path('hfpay/cfcalog.conf');
            $sign->getCFCAInitialize();

            $reslut = $sign->CFCASignature($data);
        } catch (\Exception $e) {
            app('log')->error('hf_sign_data =>' . $e->getMessage());
            throw new ResourceException('签名错误');
        }


        return $reslut;
    }

    /**
     * 汇付返回数据解密
     */
    private function sourceData($checkValue)
    {
        try {
            $sign = new HfSign();
            $sign->strPfxPassword = $this->config->pfx_password;
            $sign->strPfxFilePath = storage_path('chinapnrPayment/' . $this->config->mer_cust_id . '/cfca.pfx');
            $sign->strTrustedCACertFilePath = storage_path('chinapnrPayment/' . $this->config->mer_cust_id . '/CFCA_ACS_CA.cer') . '|' . storage_path('chinapnrPayment/' . $this->config->mer_cust_id . '/CFCA_ACS_OCA31.cer');
            $sign->strLogCofigFilePath = storage_path('hfpay/cfcalog.conf');
            $sign->getCFCAInitialize();

            //验证接口返回的签名数据
            $sourceData = $sign->getCFCASignSourceData($checkValue);
            $SignCertContent = !empty($sourceData['strMsgP7AttachedSignCertContent']) ? $sourceData['strMsgP7AttachedSignCertContent'] : '';

            //验证返回数据的CFCA证书有效性
            $verifyCertificat = $sign->verifyCertificat($SignCertContent);
            $signSourceData = '';
            if (!empty($sourceData['strMsgP7AttachedSource']) && $verifyCertificat) {  //校验证书有效性
                $signSourceData = json_decode($sourceData['strMsgP7AttachedSource'], true);
            }
        } catch (\Exception $e) {
            app('log')->error('hf_source_data =>' . $e->getMessage());
            throw new ResourceException('解密错误');
        }

        return $signSourceData;
    }
}
