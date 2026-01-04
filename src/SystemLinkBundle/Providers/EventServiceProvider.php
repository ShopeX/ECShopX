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
