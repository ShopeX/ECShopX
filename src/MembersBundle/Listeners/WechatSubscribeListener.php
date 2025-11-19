<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
