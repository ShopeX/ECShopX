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

namespace WechatBundle\OvertrueWechat;

use WechatBundle\Entities\WechatAuth;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use EasyWeChat\Factory;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class WechatManager
{
    public const SUPPORT_MINI_PROGRAMS = ['YYKZS'];

    // 获取开放平台调用实例
    public function openPlatform()
    {
        return app('easywechat.open_platform');
    }

    /**
     * 支付实例
     * @param $app_id 小程序对应的是小程序的appid,第三方app对应的是第三方app的appid，其他都是微信支付分配的公众账号ID（企业号corpid即为此appId）
     * @param $mch_id 商户号
     * @param $key API密钥
     * @param $cert_path 支付证书
     * @param $key_path 支付证书密钥
     * @param $sub_app_id 子商户appid
     * @param $sub_mch_id 子商户商户号
     */
    public function payment($app_id, $mch_id, $key, $cert_path = '', $key_path = '', $sub_app_id = '', $sub_mch_id = '')
    {
        $config = [
            // 必要配置
            'app_id' => $app_id, // 小程序对应的是小程序的appid,第三方app对应的是第三方app的appid，其他都是微信支付分配的公众账号ID（企业号corpid即为此appId）
            'mch_id' => $mch_id,
            'key' => $key, // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => $cert_path,
            'key_path' => $key_path,
        ];
        $app = app('easywechat.app.payment', $config);

        // 判断是否为子商户
        if (!empty($sub_mch_id)) {
            $app->setSubMerchant($sub_mch_id, $sub_app_id); // 子商户 AppID 为可选项
        }

        return $app;
    }

    /**
     * 支付实例
     */
    public function paymentH5($app_id, $mch_id, $key, $cert_path = '', $key_path = '', $sub_app_id = '', $sub_mch_id = '')
    {
        return $this->payment($app_id, $mch_id, $key, $cert_path, $key_path, $sub_app_id, $sub_mch_id);
    }

    /**
     * 支付实例
     */
    public function paymentApp($app_id, $mch_id, $key, $cert_path = '', $key_path = '', $sub_app_id = '', $sub_mch_id = '')
    {
        return $this->payment($app_id, $mch_id, $key, $cert_path, $key_path, $sub_app_id, $sub_mch_id);
    }

    /**
     * [merchantPayment 企业付款到零钱]
     * @param  string $wxaappid        商户账号appid
     * @param  string $merchantId      商户号
     * @param  string $key             [description]
     * @param  string $certPath        商户证书文件路径
     * @param  string $keyPath         商户证书密钥文件路径
     * @param  string $authorizerAppId 商户账号appid
     * @return object
     */
    public function merchantPayment($wxaappid, $merchantId, $key, $certPath, $keyPath, $authorizerAppId)
    {
        $config = [
            // 必要配置
            'app_id' => $wxaappid,
            'mch_id' => $merchantId,
            'key' => $key, // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => $certPath,
            'key_path' => $keyPath,
        ];
        $app = app('easywechat.app.payment', $config);

        return $app;
    }

    /**
     * 获取平台小程序调用实例 源源客助手
     */
    public function miniProgram($wxappName)
    {
        if (!in_array(strtoupper($wxappName), self::SUPPORT_MINI_PROGRAMS)) {
            throw new \InvalidArgumentException(sprintf('Do not support mini program : %s', $wxappName));
        }

        return app('easywechat.mini_program.'.strtoupper($wxappName));
    }

    /**
     * 获取公众号或小程序调用实例
     *
     * @param string $authorizerAppId 公众号或者小程序ID
     * @return \EasyWeChat\OpenPlatform\Authorizer\OfficialAccount\Application 公众号服务
     * @return \EasyWeChat\OpenPlatform\Authorizer\MiniProgram\Application 小程序服务
     */
    public function app_openplatform($authorizerAppId)
    {
        $openPlatform = app('easywechat.open_platform');
        if (!$authorizerAppId) {
            throw new BadRequestHttpException('当前账号未绑定公众号或小程序，请先授权绑定');
        }

        $authorizerRefreshToken = app('registry')->getManager('default')->getRepository(WechatAuth::class)->getAuthorizerRefreshToken($authorizerAppId);

        if (!$authorizerRefreshToken) {
            throw new BadRequestHttpException('当前公众号或小程序未绑定或已解绑，请重新授权', null, 400001);
        }
        $authorizerInfo = app('registry')->getManager('default')->getRepository(WechatAuth::class)->getAuthorizerInfo($authorizerAppId);
        $authorizerApp = null;
        if ($authorizerInfo['service_type_info'] == '3') {
            $authorizerApp = $openPlatform->miniProgram($authorizerAppId, $authorizerRefreshToken);
        } elseif ($authorizerInfo['service_type_info'] == '2') {
            $authorizerApp = $openPlatform->officialAccount($authorizerAppId, $authorizerRefreshToken);
        }
        return $authorizerApp;
    }

    // 直连小程序调用,需要整合，先替换成能用的新的easywechat @todo
    public function app_direct($authorizerAppId)
    {
        if (!$authorizerAppId) {
            throw new BadRequestHttpException('当前账号未绑定公众号或小程序，请先授权绑定');
        }

        $authorizerInfo = app('registry')->getManager('default')->getRepository(WechatAuth::class)->getAuthorizerInfo($authorizerAppId);

        if (!$authorizerInfo['authorizer_appsecret']) {
            throw new BadRequestHttpException('当前公众号或小程序未配置secret', null, 400001);
        }
        $app = null;
        $wechatConfig = config('wechat');
        $wechatConfig['app_id'] = $authorizerAppId; // 小程序或公众号AppID
        $wechatConfig['secret'] = $authorizerInfo['authorizer_appsecret']; // 小程序或公众号secret
        if ($authorizerInfo['service_type_info'] == '3') { // 小程序
            $app = Factory::miniProgram($wechatConfig);
        } elseif ($authorizerInfo['service_type_info'] == '2') { // 公众号
            $wechatConfig['response_type'] = 'array'; // 返回格式
            $app = Factory::officialAccount($wechatConfig);
        }
        // 创建缓存实例
        $cache = new RedisAdapter(app('redis')->connection()->client());
        $app->rebind('cache', $cache);
        return $app;
    }
}
