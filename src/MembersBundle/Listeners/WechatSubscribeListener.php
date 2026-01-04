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

namespace MembersBundle\Listeners;

use WechatBundle\Events\WechatSubscribeEvent;
use WechatBundle\Services\OpenPlatform;
use MembersBundle\Services\WechatFansService;

class WechatSubscribeListener
{
    /** @var wechatFansService */
    private $wechatFansService;

    public function __construct(WechatFansService $wechatFansService)
    {
        $this->wechatFansService = $wechatFansService;
    }

    /**
     * Handle the event.
     *
     * @param  SubscribeEvent  $event
     * @return void
     */
    public function handle(WechatSubscribeEvent $event)
    {
        $params = [
            'openId' => $event->openId,
            'authorizerAppId' => $event->authorizerAppId,
            'company_id' => $event->companyId,
            'event' => $event->event,
        ];

        switch ($params['event']) {
            case 'subscribe':
                return $this->subscribe($params);
                break;

            case 'unsubscribe':
                return $this->unsubscribe($params);
                break;
        }
    }

    public function subscribe($params)
    {
        $openPlatform = new OpenPlatform();
        $authorizerAppId = $params['authorizerAppId'];
        $openId = $params['openId'];
        $app = $openPlatform->getAuthorizerApplication($authorizerAppId);
        $user = $app->user->get($openId);
        if ($user && $user['unionid']) {
            $userInfo = [
                'open_id' => $openId,
                'authorizer_appid' => $authorizerAppId,
                'company_id' => $params['company_id'],
                'nickname' => $user['nickname'],
                'subscribed' => $user['subscribe'],
                'sex' => $user['sex'],
                'city' => $user['city'],
                'country' => $user['country'],
                'province' => $user['province'],
                'language' => $user['language'],
                'headimgurl' => $user['headimgurl'],
                'subscribe_time' => $user['subscribe_time'],
                'unionid' => $user['unionid'],
                'remark' => $user['remark'],
                'groupid' => $user['groupid'],
                'tagids' => implode(',', $user['tagid_list']),
            ];

            return $this->wechatFansService->addUser($userInfo);
        }
    }

    public function unsubscribe($params)
    {
        $filter = ['open_id' => $params['openId'], 'company_id' => $params['company_id'], 'authorizer_appid' => $params['authorizerAppId']];
        $data = ['subscribed' => false];
        $this->wechatFansService->delUserTag($filter);

        return $this->wechatFansService->update($filter, $data);
    }
}
