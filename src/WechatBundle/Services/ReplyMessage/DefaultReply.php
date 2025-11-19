<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Services\ReplyMessage;

class DefaultReply
{
    /**
     * 执行默认的自动回复
     */
    public function handle($message, $authorizerAppId)
    {
        $content = $this->getLastReplyContent($authorizerAppId);
        if ($content) {
            return [
                'type' => $content['reply_type'],
                'content' => $content['reply_content']
            ];
        }
    }

    /**
     * 设置默认的自动回复
     */
    public function setDefaultReplyContent($authorizerAppId, $content)
    {
        // reply_type 回复消息类型
        // reply_content 回复消息内容，文字消息则为对应的文字，其他素材消息则为对应的素材ID
        $data = json_encode([
            'reply_type' => $content['reply_type'],
            'reply_content' => $content['reply_content']
        ]);
        return app('redis')->set($this->genId($authorizerAppId), json_encode($content));
    }

    /**
     * 获取默认的自动回复内容
     */
    public function getLastReplyContent($authorizerAppId)
    {
        $data = app('redis')->get($this->genId($authorizerAppId));
        if ($data) {
            $data = json_decode($data, true);
        }
        return $data;
    }

    /**
     * 存储Redis中的key
     */
    private function genId($authorizerAppId)
    {
        return 'defaultReply:'. sha1($authorizerAppId.'message_default_autoreply_info');
    }
}
