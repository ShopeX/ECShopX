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

namespace PromotionsBundle\Services\SmsDriver;

use PromotionsBundle\Interfaces\SmsInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * shopex prism sms
 */
class ShopexSmsClient implements SmsInterface
{
    //根据新的token添加内容，用于添加短信签名
    private $addSmsSign = '/addcontent/newbytoken';

    //更新短信签名
    private $updateSmsSign = '/addcontent/updatebytoken';

    //获取请求的token
    private $authToken = '/auth/auth.gettoken';

    //发送短信接口和获取短信余额接口
    private $sendSmsUrl = '/smsv2/send';

    // 获取oauth
    private $oauthToken = '/oauth/token';

    private $passLoginUrl = 'http://sms.shopex.cn/?';

    private $companyId;
    private $shopexUid;
    private $prismClient;

    public const FAIL_MESSAGE_ENUM = [
        '1124' => '短信账户余额不足，请核实后再试',
    ];

    /**
     * ShopexSmsClient 构造函数.
     */
    public function __construct($companyId, $shopexUid)
    {
        $this->companyId = $companyId;
        $this->shopexUid = $shopexUid;
    }

    public function connection()
    {
        $this->prismClient = new \PrismClient(
            trim(config('common.prism_url')), //$url
            trim(config('common.prism_key')), //$key
            trim(config('common.prism_secret')) //$secret
        );

        $this->checkToken();

        $this->prismClient->access_token = $this->getAccessToken();

        return $this;
    }

    /**
     * add idiograph 添加短信签名
     *
     * @param content 签名内容
     */
    public function addSmsSign($content)
    {
        $params = [
            'shopexid' => $this->shopexUid,
            'content' => $content,
            'token' => $this->getForeverToken()
        ];

        $result = $this->prismClient->post($this->addSmsSign, $params, null, ['connect_timeout' => 3]);
        $result = json_decode($result, true);
        if ($result['res'] == 'error') {
            $msg = '添加短信签名失败';
            app('log')->error('get sms remainde Error :'. var_export($result, 1));
            app('log')->error('get sms remainde Error :'. var_export($params, 1));
            throw new AccessDeniedHttpException($msg);
        }

        return true;
    }

    /**
     * update idiograph 更新短信签名
     *
     * @param newcontent 新签名内容
     * @param oldcontent 旧签名内容
     */
    public function updateSmsSign($content, $oldContent)
    {
        $params = [
            'shopexid' => $this->shopexUid,
            'new_content' => $content,
            'old_content' => $oldContent,
            'token' => $this->getForeverToken()
        ];

        $result = $this->prismClient->post($this->updateSmsSign, $params, null, ['connect_timeout' => 3]);
        $result = json_decode($result, true);

        if (!$result || $result['res'] == "error") {
            $msg = isset($result['data']) ? $result['data'] : "请求更新接口出错";
            throw new AccessDeniedHttpException($msg);
        }
        return true;
    }

    /**
     * send 短信发送
     */
    public function send($contents, $sendType = 'notice')
    {
        $params = [
            'shopexid' => $this->shopexUid,
            'certi_app' => 'sms.newsend',
            'sendType' => $sendType,
            'token' => $this->getForeverToken(),
            'source' => $this->getSource(),
            'contents' => json_encode($contents),
        ];
        $params['certi_ac'] = $this->makeShopexAc($params, $this->getSourceToken());
        app('log')->debug('短信群发参数: fan-out =>'.var_export($params, 1));
        $result = $this->prismClient->post($this->sendSmsUrl, $params, null, ['connect_timeout' => 3]);
        $result = json_decode($result, true);
        app('log')->debug('短信群发结果: fan-out =>'.var_export($result, 1));
        if (!$result || $result['res'] == "fail") {
            $msg = self::FAIL_MESSAGE_ENUM[$result['msg']] ?? '短信发送失败';
            app('log')->error('send sms Error :'. $result['info']);
            throw new AccessDeniedHttpException($msg);
        }

        return true;
    }

    /**
     * @description 获取免登录地址
     * @access public
     * @param void
     * @return void
     */
    public function getSmsBuyUrl()
    {
        $data['biz_id'] = $this->encode($this->getSource());
        $data['entid'] = $this->shopexUid;
        $data['ac'] = md5($data['entid'].$this->getSourceToken());
        $data['t'] = time();

        $params['ctl'] = 'sms';
        $params['act'] = 'prdsList';
        $params['source'] = $this->encode(implode('|', $data));

        $url = $this->passLoginUrl . http_build_query($params);
        return $url;
    }

    private function pattern()
    {
        return array(
        '+' => '_1_',
        '/' => '_2_',
        '=' => '_3_',
        );
    }

    private function encode($str)
    {
        $str = base64_encode($str);
        return strtr($str, $this->pattern());
    }

    private function decode($str)
    {
        $str = strtr($str, array_flip($this->pattern()));
        return base64_decode($str);
    }

    /**
     * 查看短信余额
     */
    public function getSmsRemainder()
    {
        $params = [
            'shopexid' => $this->shopexUid,
            'certi_app' => 'sms.newinfo',
            'token' => $this->getForeverToken(),
            'source' => $this->getSource(),
        ];
        $result = $this->prismClient->post($this->sendSmsUrl, $params, null, ['connect_timeout' => 3]);
        $result = json_decode($result, true);
        if ($result['res'] == "fail") {
            $msg = $result['info'];
            app('log')->error('get sms remainde Error :'. $result['info']);
            app('log')->error('get sms remainde Error :'. var_export($params, 1));
            return null;
        }
        return $result;
    }

    /**
     * get forever token 获取永久 token
     */
    private function getForeverToken()
    {
        $token = app('redis')->connection('prism')->get($this->getTokenRedisId());
        if (!$token) {
            $token = $this->setForeverToken();
        }
        return $token;
    }

    private function getTokenRedisId()
    {
        return 'prism:'. sha1($this->shopexUid.'_'.$this->companyId.'_ForeverToken');
    }

    /**
     * set forever token 记录永久 token
     */
    private function setForeverToken()
    {
        $params['product_code'] = 'yuan_yuan_ke';
        $result = $this->prismClient->post($this->authToken, $params, null, ['connect_timeout' => 3]);

        $result = json_decode($result, true);
        if (!$result || (isset($result['status']) && $result['status'] == "error")) {
            $msg = $result['data'];
            throw new AccessDeniedHttpException($msg);
        }

        $token = $result['data']['token'];
        app('redis')->connection('prism')->set($this->getTokenRedisId(), $token);
        return $token;
    }

    /**
     * 检测 accessToke 和 refreshToke 是否有效
     */
    private function checkToken()
    {
        $accessToken = $this->getAccessToken();
        $refreshToken = $this->getRefreshToken();
        if (!$accessToken && !$refreshToken) {
            throw new AccessDeniedHttpException('登录有误,请重新登录');
        }

        if ($accessToken && $refreshToken) {
            return true;
        }

        $params['grant_type'] = 'refresh_token';
        $params['refresh_token'] = $refreshToken;

        $result = $this->prismClient->post($this->oauthToken, $params, null, ['connect_timeout' => 3]);
        $result = json_decode($result, true);
        if (isset($result['result']) && $result['result'] == 'error') {
            throw new AccessDeniedHttpException('请重新登录');
        }

        $accessToken = $result['access_token'];
        $expiresIn = $result['expires_in'];
        $refreshToken = $result['refresh_token'];
        $refreshExpires = $result['refresh_expires'];
        $this->setAccessToken($accessToken, $expiresIn);
        $this->setRefreshToken($refreshToken, $refreshExpires);
        return $accessToken;
    }

    /**
     * 获取 accessToke 记录
     */
    public function getAccessToken()
    {
        $genId = $this->accessTokenGenId();
        $accessToken = app('redis')->connection('prism')->get($genId);
        return $accessToken;
    }

    /**
     * 获取 freshToken 记录
     */
    public function getRefreshToken()
    {
        $genId = $this->refreshTokenGenId();
        $refreshToken = app('redis')->connection('prism')->get($genId);
        return $refreshToken;
    }

    /**
     * 记录accessToken
     *
     * @param accessToken
     * @param expiresIn  accessToken 过期时间
     */
    public function setAccessToken($accessToken, $expiresIn)
    {
        $genId = $this->accessTokenGenId();
        $result = app('redis')->connection('prism')->set($genId, $accessToken);
        if ($result && $expiresIn) {
            app('redis')->connection('prism')->expireat($genId, $expiresIn);
        }
        return $result;
    }

    /**
     * 记录refreshToken
     *
     * @param refreshToken
     * @param refresh_expires refresh 过期时间
     */
    public function setRefreshToken($refreshToken, $refreshExpires)
    {
        $genId = $this->refreshTokenGenId();
        $result = app('redis')->connection('prism')->set($genId, $refreshToken);
        if ($result && $refreshExpires) {
            app('redis')->connection('prism')->expireat($genId, $refreshExpires);
        }
        return $result;
    }

    private function accessTokenGenId()
    {
        return 'prism:'. sha1($this->shopexUid.'_'.$this->companyId.'_AccessToken');
    }

    private function refreshTokenGenId()
    {
        return 'prism:'. sha1($this->shopexUid.'_'.$this->companyId.'_RefreshToken');
    }

    /**
     * @description 获取业务产品ID
     * @access public
     * @param void
     * @return string
     *
     * 测试产品ID:338049
     */
    private function getSource()
    {
        return '533218';
    }

    /**
     * @description 业务产品对应的Token
     * @access public
     * @param void
     * @return string
     *
     * 测试产品token:ac584f6d022ead5f4d8b5d1e6a80a7d1
     */
    private function getSourceToken()
    {
        return '0c4b3f44cee06df91b76deaa57608fbb';
    }

    private function makeShopexAc($temp_arr, $token)
    {
        ksort($temp_arr);
        $str = '';
        foreach ($temp_arr as $key => $value) {
            if ($key != 'certi_ac') {
                $str .= $value;
            }
        }
        return strtolower(md5($str.strtolower(md5($token))));
    }
}
