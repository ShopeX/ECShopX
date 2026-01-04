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

use WechatBundle\Entities\WechatAuth;
use Dingo\Api\Exception\StoreResourceFailedException;

class Kf
{
    /**
     * 公众号实例
     *
     */
    public $app;

    public function __construct($authorizerAppId = null)
    {
        $openPlatform = new OpenPlatform();
        if (!$authorizerAppId) {
            $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        }
        $this->app = $openPlatform->getAuthorizerApplication($authorizerAppId);
    }

    /**
     * 创建微信客服帐号
     *
     * @param string $nick 客服昵称
     * @param string $avatar 客服头像本地路径
     */
    public function create($wechatId, $nick, $avatarPath = null)
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $authorizerAlias = app('registry')->getManager('default')->getRepository(WechatAuth::class)->getAuthorizerAlias($authorizerAppId);
        if (!$authorizerAlias) {
            throw new StoreResourceFailedException('当前公众号未设置微信号，请先设置微信号');
        }

        $staff = $this->app->customer_service; // 客服管理
        $account = $wechatId.'@'.$authorizerAlias;
        //添加客服
        if ($staff->create($account, $nick)) {
            //邀请绑定客服
            $staff->invite($account, $wechatId);
            //设置头像
            if ($avatarPath) {
                $staff->setAvatar($account, $avatarPath);
            }
        }

        return true;
    }

    public function lists()
    {
        $staff = $this->app->customer_service;
        $list = $staff->list();
        $kfLists = $list['kf_list'] ?? [];
        if ($kfLists) {
            $onlines = $staff->online();
            if ($onlines['kf_online_list']) {
                $onlinesKfIds = array_column($onlines['kf_online_list'], 'kf_id');
                foreach ($kfLists as $key => $row) {
                    $kfLists[$key]['is_online'] = in_array($row['kf_id'], $onlinesKfIds) ? true : false;
                }
            } else {
                return $kfLists;
            }
        }
        return $kfLists;
    }

    /**
     * 是否有客服在线
     */
    public function isOnline()
    {
        return empty($this->getOnlines()->kf_online_list) ? false : true;
    }

    /**
     * 获取所有在线客服
     */
    public function getOnlines()
    {
        $staff = $this->app->customer_service;
        return $staff->online();
    }

    /**
     * 删除客服 function
     *
     * @return void
     */
    public function delete($account)
    {
        $staff = $this->app->customer_service;
        return $staff->delete($account);
    }

    /**
     * 删除客服 function
     *
     * @return void
     */
    public function update($account, $data)
    {
        $staff = $this->app->customer_service;
        if (isset($data['nick']) && $data['nick']) {
            $staff->update($account, $data['nick']);
        }
        if (isset($data['avatarPath']) && $data['avatarPath']) {
            $staff->setAvatar($account, $data['avatarPath']); // $avatarPath 为本地图片路径，非 URL
        }

        return true;
    }

    public function __call($method, $parameters)
    {
        return $this->app->customer_service->$method(...$parameters);
    }
}
