<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class JushuitanServiceProvider extends ServiceProvider
{
    // ShopEx EcShopX Service Component
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'SystemLinkBundle\Events\Jushuitan\ItemEditEvent' => [
            'SystemLinkBundle\Listeners\UploadItemSendJushuitan', // 上传商品
        ],

        'SystemLinkBundle\Events\Jushuitan\TradeFinishEvent' => [
            'SystemLinkBundle\Listeners\TradeFinishSendJushuitan', // 订单更新发送到聚水潭
        ],

        'SystemLinkBundle\Events\Jushuitan\TradeCancelEvent' => [
            'SystemLinkBundle\Listeners\TradeCancelSendJushuitan', // 取消订单发送到聚水潭
        ],

        'SystemLinkBundle\Events\Jushuitan\TradeAftersalesEvent' => [
            'SystemLinkBundle\Listeners\TradeAftersalesSendJushuitan', // 售后申请发送到聚水潭
        ],
    ];
}
