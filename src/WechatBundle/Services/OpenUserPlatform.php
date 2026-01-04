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

namespace WechatBundle\Services;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\WechatFansService;

/**
 * 帮助用户开通并且管理第三方账号
 */
class OpenUserPlatform
{
    /**
     * 用户授权开通第三方账号并且绑定小程序和公众号
     */
    public function userAuthOpen($authorizerAppId, $companyId)
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($authorizerAppId);
        $account = $app->account;
        try {
            // 获取open_appid 如果没有则创建
            $result = $account->getBinding();
            if ($result['errcode'] > 0) {
                throw new \Exception($result['errmsg']);
            }
            $openAppid = $result['open_appid'];
        } catch (\Exception $e) {
            $openAppid = $this->openCreate($authorizerAppId);
        }

        $weappService = new WeappService();
        $list = $weappService->getWxaList($companyId);
        if ($list) {
            foreach ($list as $row) {
                // 只有通过认证的
                if ($row['verify_type_info'] != -1) {
                    $this->bindWxa($row['authorizer_appid'], $openAppid);
                }
            }
        }

        //同步粉丝信息，只要是同步unionid
        // $wechatFansService = new WechatFansService();
        // $wechatFansService->syncWechatFans($authorizerAppId, $companyId);

        return true;
    }

    /**
     * 公众号开通第三方账号
     */
    public function openCreate($authorizerAppId)
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($authorizerAppId);
        $account = $app->account;

        $result = $account->create();
        if ($result['errcode'] > 0) {
            throw new \Exception($result['errmsg']);
        }
        return $result['open_appid'];
    }

    public function checkWxaBind($authorizerAppId, $wxaAppId)
    {
        if (!config('wechat.wxa_need_open_platform')) {
            return true;
        }

        $openPlatform = new OpenPlatform();
        try {
            $officialAccount = $openPlatform->getAuthorizerApplication($authorizerAppId);
            $account = $officialAccount->account;
            $result = $account->getBinding();
            if ($result['errcode'] > 0) {
                throw new \Exception($result['errmsg']);
            }
            $officialOpenAppid = $result['open_appid'];

            $miniProgram = $openPlatform->getAuthorizerApplication($wxaAppId);
            $account = $miniProgram->account;
            $result = $account->getBinding();
            if ($result['errcode'] > 0) {
                throw new \Exception($result['errmsg']);
            }
            $wxaOpenAppid = $result['open_appid'];

            if ($officialOpenAppid == $wxaOpenAppid) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 小程序绑定第三方账号
     */
    public function bindWxa($wxaAppId, $openAppid)
    {
        $openPlatform = new OpenPlatform();
        $miniProgram = $openPlatform->getAuthorizerApplication($wxaAppId);
        $account = $miniProgram->account;

        try {
            $result = $account->getBinding();
            if ($result['errcode'] > 0) {
                throw new \Exception($result['errmsg']);
            }
            if ($result['open_appid'] == $openAppid) {
                return true;
            } else {
                $this->unbindWxa($wxaAppId, $result['open_appid']);
            }
        } catch (\Exception $e) {
        }
        // 接口文档地址：https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/2.0/api/account/bind.html#%E8%AF%B7%E6%B1%82%E5%9C%B0%E5%9D%80
        $bindResult = $account->bindTo($openAppid);
        if (empty($bindResult)) {
            throw new ResourceException("绑定失败！");
        }
        if (!empty($bindResult["errcode"])) {
            app("log")->info(sprintf("bindWxa_error:%s", jsonEncode($bindResult)));
            throw new ResourceException(sprintf("绑定失败！"));
        }
        return true;
    }

    /**
     * 小程序解绑第三方账号
     */
    public function unbindWxa($wxaAppId, $openAppid)
    {
        $openPlatform = new OpenPlatform();
        $miniProgram = $openPlatform->getAuthorizerApplication($wxaAppId);
        $account = $miniProgram->account;

        $account->unbindFrom($openAppid);
        return true;
    }
}
