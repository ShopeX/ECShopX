<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Services\ReplyMessage;

use WechatBundle\Services\Kf as WechatKf;
use Dingo\Api\Exception\StoreResourceFailedException;

class Transfer
{
    /**
     * 接收到消息转发到在线客服.
     *
     * @return mixed
     */
    public function handle($message, $authorizerAppId)
    {
        //开启多客服则判断是否有客服在线
        $wechatKf = new WechatKf($authorizerAppId);
        if ($wechatKf->isOnline() && $this->getOpenKfReply($authorizerAppId)) {
            return new \EasyWeChat\Kernel\Messages\Transfer();
        }
    }

    /**
     * 设置是否开启多客服回复
     */
    public function setOpenKfReply($authorizerAppId, $status)
    {
        $wechatKf = new WechatKf($authorizerAppId);
        $kflist = $wechatKf->lists();
        if (!$kflist && $status == 'true') {
            throw new StoreResourceFailedException('不存在客服人员，请先添加客服后再开启');
        }

        $key = 'transfer:'. sha1($authorizerAppId.'set_open_kf_reply');
        return app('redis')->set($key, $status);
    }

    /**
     * 获取多客服回复配置
     */
    public function getOpenKfReply($authorizerAppId)
    {
        $key = 'transfer:'. sha1($authorizerAppId.'set_open_kf_reply');
        return app('redis')->get($key) === 'true' ? true : false;
    }
}
