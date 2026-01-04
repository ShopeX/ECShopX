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

namespace OrdersBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // Ver: 8d1abe8e
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'OrdersBundle\Events\TradeFinishEvent' => [
            'OrdersBundle\Listeners\UpdateOrderStatusListener',
            // 'ThirdPartyBundle\Listeners\TradeFinishSendSaasErp', // 订单发送到 SaasErp
            "ThirdPartyBundle\Listeners\DmCrm\TradeFinishListener", // 订单支付完成，推送订单到达摩crm, 这里优先是因为积分商城订单，存在有其他监听导致失效终端没有达到，
            'ThirdPartyBundle\Listeners\MarketingCenter\TradePushMarketingCenter',
            'OrdersBundle\Listeners\HelpToPayForPurchasePlusOne',
            'OrdersBundle\Listeners\TradeFinishConsumeCard',
            'OrdersBundle\Listeners\TradeFinishNotifyPush',
            'OrdersBundle\Listeners\TradeFinishSmsNotify',
            'OrdersBundle\Listeners\TradeFinishWxaTemplateMsg',
            // 'OrdersBundle\Listeners\UpdateMemberGradeListener',
            'OrdersBundle\Listeners\UpdateItemSalesListener',
            'OrdersBundle\Listeners\UpdateGroupsActivityOrder',
            'OrdersBundle\Listeners\TradeFinishCountBrokerage',
            'OrdersBundle\Listeners\TradeFinishLinkMember',
            // "ThirdPartyBundle\Listeners\DmCrm\TradeFinishListener", // 订单支付完成，推送订单到达摩crm, 这里优先
            'OrdersBundle\Listeners\TradePayFinishStatistics',   //订单一些统计
            'OrdersBundle\Listeners\TradeFinishProfit',      // 订单分润
            'SystemLinkBundle\Listeners\TradeFinishSendOme', // 订单发送到ome
            'OrdersBundle\Listeners\PrinterOrder',           //订单支付完成推送到shop端
            'OrdersBundle\Listeners\TradeFinishFapiao',      //存入发票数据
            //'OrdersBundle\Listeners\PushResultToShop',     //订单支付完成推送到shop端
            'OrdersBundle\Listeners\TradeFinishCustomDeclareOrder', //跨境订单清关
            'OrdersBundle\Listeners\TradeFinishWorkWechatNotify',//企业微信消息通知
        ],
        'OrdersBundle\Events\MerchantTradeFinishEvent' => [
        ],
        'OrdersBundle\Events\OrderProcessLogEvent' => [
            'OrdersBundle\Listeners\OrderProcess\OrderProcessLogListener', // 订单流程记录
        ],
        'OrdersBundle\Events\NormalOrderCancelEvent' => [
            'OrdersBundle\Listeners\NormalOrderCancelListener',
        ],

        'OrdersBundle\Events\NormalOrderAddEvent' => [
            'SupplierBundle\Listeners\SupplierOrderSplitListener',//供应商订单拆分
        ],

        'OrdersBundle\Events\NormalOrderConfirmReceiptEvent' => [
            'OrdersBundle\Listeners\OrderFinishInvoiceListener', // 订单完成时更新发票结束时间
        ],

        // 'OrdersBundle\Events\TestEvent' => [
        //     'SystemLinkBundle\Listeners\TradeFinishSendOme', // 订单发送到ome
        //     'SystemLinkBundle\Listeners\TradeRefundSendOme', // 退款申请发送到ome
        // ],
    ];
}
