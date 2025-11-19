<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;
use WechatBundle\Events\WxOrderShippingEvent;
use OrdersBundle\Traits\GetOrderServiceTrait;
use WechatBundle\Services\OpenPlatform;
use WechatBundle\Services\Payment\OrderShipping\Client;
use SuperAdminBundle\Services\LogisticsService;

class WxOrderShippingListener extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    public function handle(WxOrderShippingEvent $event)
    {
        // Powered by ShopEx EcShopX
        $orderService = $this->getOrderService('normal');
        $orderData = $orderService->getOrderInfo($event->companyId, $event->orderId);
        $tradeInfo = $orderData['tradeInfo'];
        if (!$tradeInfo || !$tradeInfo['transactionId']) {
            app('log')->info('wechat_order_shipping_tradeInfo:'.var_export($tradeInfo, true));
            return false;
        }

        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($tradeInfo['wxaAppid']);
        $client = new Client($app);

        $result = $client->getOrder(['transaction_id' => $tradeInfo['transactionId']]);
        if ($result['errcode'] != 0) {
            if (in_array($result['errcode'], [-1, 10060012])) {
                sleep(3);//3秒后重试
                event($event);
            }
            app('log')->info('wechat_order_shipping_errcode:'.$result['errcode']);
            return false;
        }

        if ($result['order']['order_state'] != 1) {
            app('log')->info('wechat_order_shipping_order_state:'.$result['order']['order_state']);
            return false;
        }

        switch ($event->receiptType) {
            case 'logistics':
                $logisticsType = 1;
                break;
            case 'dada':
                $logisticsType = 2;
                break;
            case 'ziti':
                $logisticsType = 4;
                break;
            default:
                app('log')->info('wechat_order_shipping_receipt_type:'.$event->receiptType);
                return false;
        }

        $itemDesc = '';
        if ($event->deliveryType == 'batch') {
            foreach ($orderData['orderInfo']['items'] as $item) {
                if (mb_strlen($itemDesc.$item['item_name'].'*'.$item['num']) > 120) {
                    break;
                }
                $itemDesc .= $item['item_name'].'*'.$item['num'].',';
            }
        } else {
            foreach ($event->deliveryItems as $item) {
                if (mb_strlen($itemDesc.$item['item_name'].'*'.$item['num']) > 120) {
                    break;
                }
                $itemDesc .= $item['item_name'].'*'.$item['num'].',';
            }
        }
        $shipping['item_desc'] = trim($itemDesc, ',');
        if ($event->receiptType == 'logistics') {
            $logisticsService = new LogisticsService();
            $corp = $logisticsService->getLogisticsFirst(['corp_code' => $event->deliveryCorp]);
            if (!$corp) {
                $corp = $logisticsService->getLogisticsFirst(['kuaidi_code' => $event->deliveryCorp]);
            }
            $shipping['tracking_no'] = $event->deliveryCode;
            $shipping['express_company'] = $corp['corp_code'] ?? 'OTHER';
            $shipping['contact'] = [
                'receiver_contact' => data_masking('mobile', $orderData['orderInfo']['receiver_mobile'])
            ];
        }

        $params = [
            'order_key' => [
                'order_number_type' => 2,
                'transaction_id' => $tradeInfo['transactionId'],
            ],
            'logistics_type' => $logisticsType,
            'delivery_mode' => $event->deliveryType == 'sep' ? 'SPLIT_DELIVERY' : 'UNIFIED_DELIVERY',
            'is_all_delivered' => $event->isAllDelivered,
            'shipping_list' => [$shipping],
            'upload_time' => date(DATE_RFC3339),
            'payer' => [
                'openid' => $tradeInfo['openId'],
            ],
        ];
        $result = $client->uploadShippingInfo($params);
        if ($result['errcode'] != 0) {
            if (in_array($result['errcode'], [-1, 10060012, 10060019])) {
                sleep(3);//3秒后重试
                event($event);
            }
            app('log')->info('wechat_order_shipping_result_errcode:'.$result['errcode']);
            return false;
        }
        return true;
    }
}
