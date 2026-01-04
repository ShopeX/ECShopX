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

namespace ThirdPartyBundle\Services\FapiaoCentre;

use GuzzleHttp\Client as Client;
use CompanysBundle\Services\CompanysService;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;

// easywechat@done

class HangxinRequest
{
    public const V = '1.0';

    public $url = '';
    public $erp_node_id = '';
    public $token = '';
    public $app_id;
    public $time_out = '10';
    public $companyId = 0;

    //加密 3DES 解密
    // public $iv = "ubspnhcw";
    public $iv = "";
    public $key = "";
    public $certSetting;

    public function __construct($company_id)
    {
        $this->companyId = $company_id;
        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($company_id);
        $certService = new CertService(false, $company_id, $shopexUid);
        $this->erp_node_id = $certService->getErpBindNode();
        $this->certSetting = $certService->getCertSetting();
        $this->url = config('common.fapiao_hangxin_api_url') ?? "http://fw2test.shdzfp.com:15002/sajt-shdzfp-sl-http/SvrServlet";
        $this->token = $this->certSetting['token'];
        $this->app_id = config('common.verify_app_id');
        $this->key = config('common.fapiao_hangxin_key');
    }

    public function call($data)
    {
        try {
            $client = new Client();

            $t1 = microtime(true);
            // $resData = $client->post($this->url, [
            //     'verify' => false,
            //     'form_params' => $data
            // ])->getBody();
            $this->url = config('common.fapiao_hangxin_api_url') ?? "http://fw2test.shdzfp.com:15002/sajt-shdzfp-sl-http/SvrServlet";

            $options = [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => $data,
            ];
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":url:". json_encode($this->url));
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":data:". ($data));

            // $resData = $client->request('POST', $this->url, $options);
            // $resData = $client->post($this->url, ['body' => $data] )->getBody();
            $resData = $this->postxml($this->url, $data);
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":dataenc:". json_encode($resData));


            return $resData;
        } catch (\Exception $e) {
            $errorMsg = 'Error on line '.$e->getLine().' in '.$e->getFile().': <b>'.$e->getMessage()."\n\n";
            app('log')->debug('saaserp error:'.$errorMsg);
            $response = [];
        }

        return $response;
    }


    public function postxml($url, $xml_data)
    {
        $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_MUTE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml_data");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    //加密 3des
    public function encrypt3des($str, $key = false)
    {
        $key = $key ? $key : $this->key;
        $data = openssl_encrypt($str, 'des-ede3', $key, 0);
        return $data;
    }


    //解密 3des
    public function decrypt3des($str, $key = false)
    {
        $key = $key ? $key : $this->key;
        $data = openssl_decrypt($str, 'des-ede3', $key, 0);
        return $data;
    }
}
