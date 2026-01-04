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

//内容检测
namespace WsugcBundle\Services;

use WsugcBundle\Entities\Post;
use MembersBundle\Services\MemberService;
use CompanysBundle\Services\CompanysService;
use WsugcBundle\Services\BadgeService;
use WsugcBundle\Services\TopicService;
use WsugcBundle\Services\SettingService;
use GuzzleHttp\Client as Client;

class ContentCheckService
{
    private $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }
    function getAccessToken()
    {
        //｛“contentCheck_enable“:1,//是否启用，后台加一个开关吧。“contentCheck_url“:““,“contentCheck_appid“:““,“contentCheck_appsecret“:““,｝
        $settingService = new SettingService();
        if ($settingService->getSetting($this->companyId, 'wx.expires_time') && $settingService->getSetting($this->companyId, 'wx.expires_time') > time() && $settingService->getSetting($this->companyId, 'wx.access_token')) {
            $access_token = $settingService->getSetting($this->companyId, 'wx.access_token');
        } else {
            $appid      =$settingService->getSetting($this->companyId, 'contentCheck_appid');
            $appsecret  =$settingService->getSetting($this->companyId, 'contentCheck_appsecret');
            $url        = $settingService->getSetting($this->companyId, 'contentCheck_url');
            $url        = $url . '/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret;
            // $resData = $client->post($post_url, [
            //     'form_params' => $post_data
            // ])->getBody();
            app('log')->debug('msgCheck-文本审查结果请求token: 用第三方平台的appid和secret:' . $url);

            $client = new Client();
            $resData = $client->get($url)->getBody();
            $responseData = json_decode($resData->getContents(), true);
            $access_token = $responseData['access_token'];
            $expires_time = $responseData['expires_in'] + time() - 60; //过期时间
            $settingService->saveSettingToRedis($this->companyId, 'wx.access_token', $access_token);
            $settingService->saveSettingToRedis($this->companyId, 'wx.expires_time', $expires_time);
        }
        return  $access_token;
    }
    /**
     * 文本检测
     * @param [string] $text
     * @param [string] $text
     * @return array
     */
    function msgCheck($text = "", $open_id)
    {
        $settingService = new SettingService();
        $enable = $settingService->getSetting($this->companyId, 'contentCheck_enable');
        if ($enable) {
            try {
                if ($access_token = $this->getAccessToken()) {
                    $url = $settingService->getSetting($this->companyId, 'contentCheck_url');
                    $url = $url . '/wxa/msg_sec_check?access_token=' . $access_token;
                    $client = new Client();
                    $post_data['openid'] = $open_id;
                    $post_data['scene'] = 2; //场景枚举值（1 资料；2 评论；3 论坛；4 社交日志）
                    $post_data['version'] = 2;
                    $post_data['content'] = $text;
                    //中文不转义
                    $post_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                    $resData = $this->requestWxApi($url, $post_data);
                    $responseData = json_decode($resData, true);
                    app('log')->debug('msgCheck-文本审查结果: open_id:' . $open_id . " \r\n提交参数：\r\n" . $post_data . "\r\n 接口返回结果：" . var_export($responseData, true));
                    if ($responseData['errcode'] == 0 && ($responseData['result'] ?? null)) {
                        $msgCheckResult = $responseData['result']['suggest']; //risky、pass、review
                    } else {
                        app('log')->debug('msgCheck-文本审查结果-接口返回失败: open_id:' . $open_id . " \r\n提交参数：\r\n" . $post_data . "\r\n 接口返回结果：" . var_export($responseData, true));
                        $msgCheckResult = 'review';
                    }
                } else {
                    app('log')->debug('msgCheck-文本审查结果-access_token获取失败');
                    $msgCheckResult = 'review';
                }
            } catch (\Exception $e) {
                app('log')->debug('msgCheck-文本审查结果-报错了' . $e->getMessage());
                $msgCheckResult = 'review';
            }
            $params['status'] = 0; //默认待审核
            if ($msgCheckResult == 'pass') {
                //只有pass时是 通过
                $params['status'] =  1;
            } else if ($msgCheckResult == 'risky') {
                //risky，机器拒绝 到 4里吧
                $params['status'] =  4;
            } else if ($msgCheckResult == 'review') {
                //risky，直接拒绝
                $params['status'] =  0;
            } else {
                //待审核
                $params['status'] =  0;
            }
        } else {
            $params['status'] =  0;
        }

        return $params['status'];
    }


    /**
     * 媒体检测 返回traceId
     * @param [string] $text
     * @param [string] $text
     * @return array
     */
    function mediaCheck($text = "", $open_id)
    {
        $trace_id = '';
        $settingService = new SettingService();
        $enable = $settingService->getSetting($this->companyId, 'contentCheck_enable');
        if ($enable) {
            try {
                if ($access_token = $this->getAccessToken()) {
                    $url = $settingService->getSetting($this->companyId, 'contentCheck_url');
                    $url = $url . '/wxa/media_check_async?access_token=' . $access_token;
                    $client = new Client();
                    $post_data['openid'] = $open_id;
                    $post_data['scene'] = 2; //场景枚举值（1 资料；2 评论；3 论坛；4 社交日志）
                    $post_data['version'] = 2;
                    $post_data['media_url'] = $text;
                    $post_data['media_type'] = '1'; //1图片

                    //中文不转义
                    $post_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                    $resData = $this->requestWxApi($url, $post_data);
                    $responseData = json_decode($resData, true);
                    app('log')->debug('mediaCheck-媒体: open_id:' . $open_id . " \r\n提交参数：\r\n" . $post_data . "\r\n 接口返回结果：" . var_export($responseData, true));
                    if ($responseData['errcode'] == 0 && ($responseData['trace_id'] ?? null)) {
                        $trace_id = $responseData['trace_id'];
                    } else {
                        app('log')->debug('mediaCheck-媒体审查结果-接口返回失败: open_id:' . $open_id . " \r\n提交参数：\r\n" . $post_data . "\r\n 接口返回结果：" . var_export($responseData, true));
                        $msgCheckResult = 'review';
                    }
                } else {
                    app('log')->debug('mediaCheck-媒体-access_token获取失败');
                    $msgCheckResult = 'review';
                }
            } catch (\Exception $e) {
                app('log')->debug('mediaCheck-媒体审查结果-报错了' . $e->getMessage());
                $msgCheckResult = 'review';
            }
         
        } else {
            //$params['status'] =  0;
        }
        
        return $trace_id;
    }

    /**
     * curl post 请求
     *
     * @param string $url
     * @param array $aHeader
     * @param string $sParams
     * @param string $cookie
     * @return string
     */
    public function requestWxApi($url, $sParams, $cookie = '')
    {
        $ch = curl_init();
        $aHeader = array(
            'Content-Type: application/json; charset=utf-8',
            'Expect:',
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sParams);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        if ($result === false) {
            // log curl_error($ch);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }
}
