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

namespace MembersBundle\Services;

use WechatBundle\Services\OfficialAccountService;
use WechatBundle\Services\OpenPlatform;
use Overtrue\Socialite\SocialiteManager;

class TrustLoginService
{
    public $key = 'trustlogin_config_';

    /**
     * 获取信任登录列表
     *
     * @param $company_id     公司id
     *
     * @return array
     */
    public function getTrustLoginList($companyId)
    {
        // ShopEx EcShopX Core Module
        $keyStr = $this->key. $companyId;
        $redis = app('redis')->connection('default');
        $result = $redis->get($keyStr);

        if (empty($result)) {
            $result = config('trustlogin');
            $redis->set($keyStr, json_encode($result));
        } else {
            $result = json_decode($result, 1);
        }
        return $result;
    }

    /**
     * 获取信任登录配置信息，前后端分离情况下，将第三方需要的信息传给前端
     *
     * @param $company_id     公司id
     * @param $trustlogin_tag 信任登录标签
     * @param $data 登录授权信息
     *
     * @return array
     */
    public function trustLoginParams($companyId, $trustlogin_tag, $version_tag = 'standard', $data)
    {
        if ($version_tag == 'standard') {
            switch ($trustlogin_tag) {
                case 'weixin':
                    $configInfo = $this->getConfigRow($trustlogin_tag, $version_tag, $companyId);
                    $config = [
                        'wechat' => [
                            'client_id' => $configInfo['app_id'],
                            'client_secret' => $configInfo['secret'],
                            'redirect' => $data['redirect_url'],
                        ],
                    ];
                    $socialite = new SocialiteManager($config);

                    $response = $socialite->driver('wechat')->redirect();
                    $redirect_url = $response->getTargetUrl();
                    $result['config_info'] = $configInfo;
                    $result['redirect_url'] = $redirect_url;
                    break;

                default:
                    $result = [];
                    break;
            }
        } elseif ($version_tag == 'touch') {
            switch ($trustlogin_tag) {
                case 'weixin':
                    $result['oauth_url'] = '';
                    $h5_host = config('common.h5_base_url') ?: $data['h5_host']; // 如果没有配置域名则默认使用原始请求域名
                    $path = $data['redirect_url'] ? ('?redi_url='. $data['redirect_url']) : '';

                    $url = sprintf("%s/subpages/auth/auth-loading%s", $h5_host, $path);

                    // 获取微信公众号的对象
                    $app = (new OpenPlatform())->getWoaApp([
                        "company_id" => $companyId,
                        "trustlogin_tag" => $trustlogin_tag, // weixin
                        "version_tag" => $version_tag // touch
                    ]);
                    if ($oauthUrl = (new OfficialAccountService($app))->getAuthorizationUrl($url)) {
                        $result['oauth_url'] = $oauthUrl;
                    }
//                    $openPlatform = new OpenPlatform;
//                    $woa_appid = $openPlatform->getWoaAppidByCompanyId($companyId);
//                    //公众号授权模式
//                    if (!empty($woa_appid)) {
//                        $app = $openPlatform->getAuthorizerApplication($woa_appid);
//                        $result['oauth_url'] = (new OfficialAccountService($app))->getAuthorizationUrl($url);
//                    } else {
//                        //普通填参模式
//                        $configInfo = $this->getConfigRow($trustlogin_tag, $version_tag, $companyId);
//                        if (!empty($configInfo) && !empty($configInfo["status"])) {
//                            $app = app('easywechat.official_account', $configInfo);
//                            $result['oauth_url'] = (new OfficialAccountService($app))->getAuthorizationUrl($url);
//                        }
//                    }
                    break;
                default:
                    $result = [];
                    break;
            }
        }
        return $result;
    }

    /**
     * 保存配置
     *
     * @param $company_id     公司id
     *
     * @return array
     */
    public function saveStatusSetting($data, $companyId)
    {
        $keyStr = $this->key. $companyId;
        $redis = app('redis')->connection('default');
        $result = $redis->get($keyStr);
        if (empty($result)) {
            return false;
        }
        $result = json_decode($result, 1);
        if (!isset($result[$data['loginversion']])) {
            return false;
        }
        $editVersion = $result[$data['loginversion']];

        $rowResult = collect($editVersion)->firstWhere('type', $data['type']);
        foreach ($rowResult as $key => &$value) {
            if (isset($data[$key])) {
                $value = $data[$key];
            }
        }
        foreach ($editVersion as $k => &$config) {
            if ($config['type'] == $data['type']) {
                $config = $rowResult;
            }
        }
        $result[$data['loginversion']] = $editVersion;
        $redis->set($keyStr, json_encode($result));

        return true;
    }

    public function getConfigRow($type, $version = 'standard', $companyId)
    {
        $keyStr = $this->key. $companyId;
        $redis = app('redis')->connection('default');
        $result = $redis->get($keyStr);
        if (empty($result)) {
            return [];
        }
        $result = json_decode($result, 1);
        if (!isset($result[$version])) {
            return [];
        }
        $editVersion = $result[$version];

        $rowResult = collect($editVersion)->firstWhere('type', $type);

        return $rowResult;
    }
}
