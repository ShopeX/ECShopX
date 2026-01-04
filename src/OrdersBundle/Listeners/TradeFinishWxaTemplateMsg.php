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

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use WechatBundle\Services\OpenPlatform;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use WorkWechatBundle\Jobs\sendWaitingDeliveryNoticeJob;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;

class TradeFinishWxaTemplateMsg extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;
    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        // ShopEx EcShopX Service Component
        if ($event->entities->getTradeSourceType() == 'membercard') {
            return true;
        }

        $payTime = $event->entities->getTimeStart();
        $payType = $event->entities->getPayType();
        if ($payType == 'wxpay') {
            $payTypeName = '微信支付';
        } elseif ($payType == 'deposit') {
            $payTypeName = '余额支付';
        } else {
            $payTypeName = null;
        }
        $shopName = '';
        if ($payTypeName) {
            try {
                $companyId = $event->entities->getCompanyId();
                $orderId = $event->entities->getOrderId();
                $orderAssociationService = new OrderAssociationService();
                $order = $orderAssociationService->getOrder($companyId, $orderId);
                if (!$order) {
                    app('log')->debug('支付成功发送订阅消息失败: 找不到订单');
                    return true;
                }
                $orderService = $this->getOrderServiceByOrderInfo($order);
                $result = $orderService->getOrderInfo($companyId, $orderId);
                $order = $result['orderInfo'] ?? [];
                $trade = $result['tradeInfo'] ?? [];
                if (!$order) {
                    app('log')->debug('支付成功发送订阅消息失败: 找不到订单');
                    return true;
                }
                if (!$trade) {
                    app('log')->debug('支付成功发送订阅消息失败: 找不到支付单');
                    return true;
                }
                if (($order['distributor_id'] ?? 0) && $order['receipt_type'] != 'ziti') {
                    // 导购待发货通知
                    $gotoJob = (new sendWaitingDeliveryNoticeJob($companyId, $orderId, $order['distributor_id']))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                }
                // 积分支付订单不需要
                if (in_array($event->entities->getPayType(), ['point', 'deposit'])) {
                    return true;
                }

                if (isset($order['order_class']) && $order['order_class'] != 'community') {
                    $shopId = $event->entities->getShopId();
                    if ($shopId) {
                        $shopsService = new ShopsService(new WxShopsService());
                        $shopInfo = $shopsService->getShopInfoByShopId($shopId);
                        if ($shopInfo) {
                            $shopName = (isset($shopInfo['store_name']) && $shopInfo['company_id'] == $companyId) ? $shopInfo['store_name'] : '';
                        }
                    }
                }
                if (!$shopName) {
                    $wxaAppId = $event->entities->getWxaAppid();
                    $openPlatform = new OpenPlatform();
                    $authorizationInfo = $openPlatform->getAuthorizerInfo($wxaAppId);
                    $shopName = $authorizationInfo['nick_name'] ?: $authorizationInfo['principal_name'];
                }

                $wxaTemplateMsgData = [
                    'pay_money' => bcdiv($trade['payFee'], 100, 2),
                    'pay_date' => $trade['payDate'],//date("Y-m-d H:i:s", $trade['payDate']),
                    'item_name' => $trade['detail'] ?? '',
                    'shop_name' => $shopName,
                    'order_id' => $trade['orderId'],
                    'trade_id' => $trade['tradeId'],
                    'receipt_type' => $order['receipt_type'] == 'ziti' ? '门店自提' : '物流配送',
                    'pay_type' => $payTypeName,
                ];
                $sendData['scenes_name'] = 'paymentSucc';
                $sendData['company_id'] = $companyId;
                $sendData['appid'] = $event->entities->getWxaAppid();
                $sendData['openid'] = $event->entities->getOpenId();
                $sendData['data'] = $wxaTemplateMsgData;
                app('wxaTemplateMsg')->send($sendData);
            } catch (\Exception $e) {
                app('log')->debug('支付成功发送订阅消息失败: '.$e->getMessage());
            }
        }
    }
}
