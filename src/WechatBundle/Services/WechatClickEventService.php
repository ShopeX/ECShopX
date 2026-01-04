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
