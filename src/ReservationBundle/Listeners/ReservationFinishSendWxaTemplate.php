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

namespace ReservationBundle\Listeners;

use ReservationBundle\Events\ReservationFinishEvent;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use MembersBundle\Services\WechatUserService;

class ReservationFinishSendWxaTemplate
{
    public function handle(ReservationFinishEvent $event)
    {
        try {
            $data = $event->entities;
            $postdata = $data['postdata'];
            $result = $data['result'];
            $params = array_merge($postdata, $result);
            if (!($params['user_id'] ?? 0) || !($params['company_id'] ?? 0)) {
                app('log')->debug('微信支付通知短信发送失败: 用户信息有误');
                return true;
            }
            if ($params['status'] == 'system') {
                app('log')->debug('微信支付通知短信发送失败: 系统占位不需要发送通知');
                return true;
            }
            $shopId = $params['shop_id'];
            $shopsService = new ShopsService(new WxShopsService());
            $shopInfo = $shopsService->getShopInfoByShopId($shopId);
            if (!$shopInfo) {
                app('log')->debug('预约到店发送消息通知出错：门店数据有误');
                return true;
            }
            $toShopTime = strtotime($params['date_day'].$params['begin_time']);

            if (!($params['wxapp_appid'] ?? '')) {
                $wechatUserService = new WechatUserService();
                $unionid = $wechatUserService->getUnionidByUserId($params['user_id'], $params['company_id']);
                if (!$unionid) {
                    app('log')->debug('微信支付通知短信发送失败: 用户信息有误');
                    return true;
                }
                $user = $wechatUserService->getSimpleUser(['unionid' => $unionid, 'company_id' => $params['company_id']]);
                if (!$user) {
                    app('log')->debug('微信支付通知短信发送失败: 用户信息有误');
                    return true;
                }
                $params['wxapp_appid'] = $user['authorizer_appid'];
                $params['open_id'] = $user['open_id'];
            }
            //发送小程序模版消息通知
            $wxaTemplateMsgData = [
                'user_name' => $params['user_name'],
                'date' => date('Y-m-d H:i:s', $toShopTime),
                'name' => $params['rights_name'],
                'shop_name' => $shopInfo['store_name'],
                'shop_address' => $shopInfo['address'],
                'shop_contract_phone' => $shopInfo['contract_phone'],
            ];

            $sendData['scenes_name'] = 'reservationSucc';
            $sendData['company_id'] = $shopInfo['company_id'];
            $sendData['appid'] = $params['wxapp_appid'];
            $sendData['openid'] = $params['open_id'];
            $sendData['data'] = $wxaTemplateMsgData;
            app('wxaTemplateMsg')->send($sendData);
        } catch (\Exception $e) {
            app('log')->debug('预约成功发送消息通知出错：' . $e->getMessage());
        }
    }
}
