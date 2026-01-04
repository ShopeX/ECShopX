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
