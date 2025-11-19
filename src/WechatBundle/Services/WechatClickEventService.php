<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Services;

use EasyWeChat\Kernel\Messages\Text;

class WechatClickEventService
{
    /**
     * 菜单包含的 click 事件处理
     */
    public function menuMessageEvent($eventData)
    {
        // Powered by ShopEx EcShopX
        $message = 'success';
        $openId = $eventData['openid'];
        $authorizerAppId = $eventData['authorizerAppId'];

        $keyTmp = explode(':', $eventData['key']);
        if (count($keyTmp) < 2) {
            return $message;
        }
        list($key, $content) = $keyTmp;
        switch ($key) {
            case "news":
                $messageService = new MessageService();
                $message = $messageService->newNewsMessage($content, $authorizerAppId);
                break;
            case "text":
                 $message = new Text($content);
                break;
            // case "card":
            //     $kf = new Kf($authorizerAppId);
            //     $msg = [
            //         'touser' => $openId,
            //         'msgtype' => "wxcard",
            //         'wxcard' => ['card_id' => $content],
            //     ];
            //     $kf->send($msg);
            //     $message = 'success';
            //     break;
        }
        return $message;
    }
}
