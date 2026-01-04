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
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Voice;
use EasyWeChat\Kernel\Messages\News;
use WechatBundle\Services\ReplyMessage\SubscribeReply;

class MessageService
{
    // 发送者
    public $fromUserName;

    private $replyMessageServers = [
        // 如果为文字消息或者语音消息并且已经识别，则先调用关键字自动回复
        \WechatBundle\Services\ReplyMessage\Autoreply::class,
        // 如果自动回复规则没有触发则调用多客服回复
        \WechatBundle\Services\ReplyMessage\Transfer::class,
        // 如果多客服没有触发则调用默认的消息自动回复配置 例如：客服服务时间为9:00-18:00，请在该时间段进行咨询
        \WechatBundle\Services\ReplyMessage\DefaultReply::class
    ];

    /**
     * 服务器接收到微信的消息，进行响应回复
     *
     * @param object $message
     * @param string $authorizerAppId
     */
    public function replyMessage($message, $authorizerAppId)
    {
        $this->fromUserName = $message['FromUserName'];
        foreach ($this->replyMessageServers as $service) {
            $servicesObject = new $service();
            $result = $servicesObject->handle($message, $authorizerAppId);
            if ($result) {
                break;
            }
        }

        if (is_object($result)) {
            return $result;
        }

        //如果不是多客服转发
        //发送消息
        if ($result && !is_object($result)) {
            $result = $this->newMessage($result, $authorizerAppId);
        }

        return $result;
    }

    /**
     * 被关注消息回复
     */
    public function subscribeReply($message, $authorizerAppId)
    {
        $this->fromUserName = $message['FromUserName'];

        $subscribeReply = new SubscribeReply();
        $data = $subscribeReply->handle($authorizerAppId);
        if ($data['content']) {
            return $this->newMessage($data, $authorizerAppId);
        }
    }

    //实例化消息
    public function newMessage(array $content, $authorizerAppId)
    {
        if (empty($content) || !$content['content']) {
            return '';
        }

        switch ($content['type']) {
        case 'text':
            $text = new Text($content['content']);
            break;
        case 'image':
            $imageData = $content['content'];
            $text = new Image($imageData['media_id']);
            break;
        case 'voice':
            $text = new Voice($content['content']);
            break;
        case 'news':
            $text = $this->newNewsMessage($content['content'], $authorizerAppId);
            break;
        // case 'card':
        //     $text = $this->sendCardMessage($content['content'], $authorizerAppId, null);
        //     break;
        }
        return $text;
    }

    /**
     * 自动回复卡券
     */
    public function sendCardMessage($cardData, $authorizerAppId, $userOpenId = null)
    {
        $kf = new Kf($authorizerAppId);
        $msg = [
            'touser' => $userOpenId ? $userOpenId : $this->fromUserName,
            "msgtype" => "wxcard",
            'wxcard' => [
                'card_id' => $cardData['card_id'],
            ]
        ];
        $kf->send($msg);
        return 'success';
    }

    /**
     * 实例化图文消息 function
     *
     * @return array
     */
    public function newNewsMessage($mediaId, $authorizerAppId)
    {
        $material = new Material();
        $list = $material->application($authorizerAppId)->getMaterial($mediaId);
        $news = [];
        if (isset($list['news_item']) && $list['news_item']) {
            foreach ($list['news_item'] as $row) {
                $news[] = new News([
                    'title' => $row['title'],
                    'description' => $row['digest'],
                    'image' => $row['thumb_url'],
                    'url' => $row['url'],
                ]);
            }
        }

        return $news;
    }

    /**
     * 获取回复设置的具体内容
     */
    public function replySettingContent($type, $content, $authorizerAppId)
    {
        if (empty($content)) {
            return '';
        }

        $material = new Material();
        $material = $material->application($authorizerAppId);
        $data = [];
        switch ($type) {
            case 'text':
                $data = $content;
                break;
            case 'image':
                $data = $content;
                break;
            case 'voice':
            case 'news':
                $newsData = $material->getMaterial($content);
                $data['content'] = $newsData;
                $data['media_id'] = $content;
                break;
            // case 'card':
            //     $data = $content;
        }
        return $data;
    }
}
