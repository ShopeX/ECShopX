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

use GuzzleHttp\Client as Client;
use OrdersBundle\Entities\CustomDeclareOrderResult;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;

use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Services\CustomsService;
use EasyWeChat\Kernel\Support\XML; // easywechat@done

use ThirdPartyBundle\Events\CustomDeclareOrderEvent;

class TradeFinishCustomDeclareOrder
{
    use GetOrderServiceTrait;

    public $url = 'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclareorder';

    /**
     * @param TradeFinishEvent $event
     */
    public function handle(TradeFinishEvent $event)
    {
        // app('log')->debug("\n 清关 TradeFinishCustomDeclareOrder event=>:".var_export($event->entities, 1));

        // 积分支付订单不需要
        if (in_array($event->entities->getPayType(), ['point', 'deposit'])) {
            return true;
        }

        if ($event->entities->getTradeSourceType() == 'membercard') {
            return true;
        }

        $payTime = $event->entities->getTimeStart();
        $payType = $event->entities->getPayType();
        if ($payType == 'wxpay') {
            try {
                $companyId = $event->entities->getCompanyId();
                $appid = $event->entities->getWxaAppid();
                $distributorId = $event->entities->getDistributorId();
                $customsService = new CustomsService($companyId, $appid, $distributorId);

                $orderId = $event->entities->getOrderId();
                $orderAssociationService = new OrderAssociationService();
                $order = $orderAssociationService->getOrder($companyId, $orderId);
                if (!$order) {
                    app('log')->debug('支付成功清关: 找不到订单');
                    return true;
                }
                $orderService = $this->getOrderServiceByOrderInfo($order);
                $result = $orderService->getOrderInfo($companyId, $orderId);
                $order = $result['orderInfo'] ?? [];
                $trade = $result['tradeInfo'] ?? [];
                //普通订单不接入微信清关
                if ($order['type'] == 0) {
                    return true;
                }

                $parameters['out_trade_no'] = $trade['tradeId'];//商户订单号
                $parameters['customs'] = 'GUANGZHOU_ZS';//海关
                $parameters['mch_id'] = $trade['mchId'];//支付id
                $parameters['transaction_id'] = $trade['transactionId'];//财付通交易号

                $pageData['dsss'] = $customsService->getParameters($parameters);
                $xmlData = "
                        <xml>
                           <appid>" .$appid. "</appid>
                           <customs>GUANGZHOU_ZS</customs>
                           <mch_customs_no>". config('common.owner_id') ."</mch_customs_no>
                           <mch_id>" .$trade['mchId']. "</mch_id>
                           <out_trade_no>". $trade['tradeId'] ."</out_trade_no>
                           <sign>" . $pageData['dsss']['sign'] . "</sign>
                           <transaction_id>". $trade['transactionId'] ."</transaction_id>
                        </xml>";
                $client = new Client();

                $resData = $client->post($this->url, [
                    'verify' => false,
                    'headers' => [
                        'Content-Type' => 'text/xml'
                    ],
                    'body' => $xmlData
                ])->getBody();
                $result = XML::parse($resData);
                app('log')->debug('清关返回信息：' . var_export($result, 1));

                if ($result['return_code'] == 'SUCCESS') {
                    if ($result['result_code'] == 'FAIL') {
                        //失败
                        $errorMsg = '错误代码=>' . $result['err_code'] . ' 错误代码描述=>'. $result['err_code_des'];
                        app('log')->debug("清关失败:". $errorMsg);
                    }

                    if ($result['result_code'] == 'SUCCESS') {
                        //成功-记录请求结果
                        $result['order_id'] = $orderId;
                        $result['trade_id'] = $result['out_trade_no'];
                        $result['company_id'] = $companyId;
                        $entityRepository = app('registry')->getManager('default')->getRepository(CustomDeclareOrderResult::class);
                        $entityRepository->create($result);
                        //event(new CustomDeclareOrderEvent($event->entities));
                    }
                }

                if ($result['return_code'] == 'FAIL') {
                    $errorMsg = '错误返回信息=>'. $result['return_msg'];
                    app('log')->debug("清关失败:". $errorMsg);
                }
            } catch (\Exception $e) {
                $errorMsg = "TradeFinishCustomDeclareOrder Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
                app('log')->debug("清关失败:". $errorMsg);
            }
        }
        return true;
    }
}
