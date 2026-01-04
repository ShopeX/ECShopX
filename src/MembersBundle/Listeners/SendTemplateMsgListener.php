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

use GoodsBundle\Events\ItemStoreUpdateEvent;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\GoodsArrivalNoticeService;

class SendTemplateMsgListener
{
    public function handle(ItemStoreUpdateEvent $event)
    {
        $distributor_id = $event->distributor_id ?? 0;
        $item_id = $event->item_id;
        $store = $event->store;
        app('log')->debug('更新库存：item_id===>' . $item_id . '  store===>' . $store);

        if ($store <= 0) {
            return true;
        }

        $itemService = new ItemsService();
        $info = $itemService->get($item_id);
        if (!$info) {
            return true;
        }

        $filter = [
            'company_id' => $info['company_id'],
            'item_id' => is_array($item_id) ? $item_id : [$item_id],
            'distributor_id' => $distributor_id,
        ];

        $goodsArrivalNotice = new GoodsArrivalNoticeService();
        // 微信通知
        $list = $goodsArrivalNotice->getList($filter, 'wechat');

        app('log')->debug('wechat通知列表：' . var_export($list, 1));

        foreach ($list as $v) {
            if (!$v['wxa_appid'] || !$v['open_id']) {
                continue;
            }
            //发送小程序模版
            $wxaTemplateMsgData = [
                'item_name' => mb_strlen($v['item_name'], 'utf-8') > 20 ? mb_substr($v['item_name'], 0, 20, 'utf-8') : $v['item_name'],
                'notice' => '亲！您想要的商品已经到货，可以购买啦！',
            ];
            $sendData['scenes_name'] = 'goodsArrivalNotice';
            $sendData['company_id'] = $info['company_id'];
            $sendData['appid'] = $v['wxa_appid'];
            $sendData['openid'] = $v['open_id'];
            $sendData['data'] = $wxaTemplateMsgData;
            $sendData['page_query_str'] = "id=" . $v['item_id'];
            app('wxaTemplateMsg')->send($sendData);

            //更新通知状态
            $goodsArrivalNotice->updateBy(['rel_id' => $v['item_id'], 'user_id' => $v['user_id'], 'distributor_id' => $filter['distributor_id'], 'sub_status' => 'NO'], ['sub_status' => 'SUCCESS']);
        }
        foreach ($filter['item_id'] as $v) {
            $goodsArrivalNotice->deleteAll($info['company_id'], 'wechat', $v, $filter['distributor_id']);
        }

        // 支付宝通知
        $list = $goodsArrivalNotice->getList($filter, 'alipay');

        app('log')->debug('alipay通知列表：' . var_export($list, 1));

        foreach ($list as $v) {
            if (!$v['open_id']) {
                continue;
            }
            //发送小程序模版
            $templateMsgData = [
                'item_name' => mb_strlen($v['item_name'], 'utf-8') > 20 ? mb_substr($v['item_name'], 0, 20, 'utf-8') : $v['item_name'],
                'notice' => '亲！您想要的商品已经到货，可以购买啦！',
            ];
            $sendData['scenes_name'] = 'goodsArrivalNotice';
            $sendData['company_id'] = $info['company_id'];
            $sendData['to_user_id'] = $v['open_id'];
            $sendData['data'] = $templateMsgData;
            $sendData['page_query_str'] = "id=" . $v['item_id'];
            app('aliTemplateMsg')->send($sendData);

            //更新通知状态
            $goodsArrivalNotice->updateBy(['rel_id' => $v['item_id'], 'user_id' => $v['user_id'], 'distributor_id' => $filter['distributor_id'], 'sub_status' => 'NO'], ['sub_status' => 'SUCCESS']);
        }
        foreach ($filter['item_id'] as $v) {
            $goodsArrivalNotice->deleteAll($info['company_id'], 'alipay', $v, $filter['distributor_id']);
        }
    }
}
