<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // 0x456353686f7058
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // 'SystemLinkBundle\Events\TradeFinishEvent' => [
        //     'SystemLinkBundle\Listeners\TradeFinishSendOme', // 订单发送到ome
        // ],

        'SystemLinkBundle\Events\TradeUpdateEvent' => [
            'SystemLinkBundle\Listeners\TradeUpdateSendOme', // 订单更新发送到ome
        ],

        'SystemLinkBundle\Events\TradeRefundEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\TradeRefundPushMarketingCenter',
            'SystemLinkBundle\Listeners\TradeRefundSendOme', // 退款申请发送到ome
        ],

        'SystemLinkBundle\Events\TradeRefundFinishEvent' => [
            'SystemLinkBundle\Listeners\TradeRefundFinishSendOme', // 退款成功发送到ome
        ],

        'SystemLinkBundle\Events\TradeAftersalesEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\TradeAftersalesPushMarketingCenter',
            'SystemLinkBundle\Listeners\TradeAftersalesSendOme', // 售后申请发送到ome
        ],

        'SystemLinkBundle\Events\TradeAftersalesCancelEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\TradeAftersalesCancelPushMarketingCenter',
            'SystemLinkBundle\Listeners\TradeAftersaleCancelSendOme', //售后取消
        ],

        'SystemLinkBundle\Events\TradeAftersalesLogiEvent' => [
            'SystemLinkBundle\Listeners\TradeAfterLogiSendOme', //退货物流信息发送到ome
        ],


    ];
}
