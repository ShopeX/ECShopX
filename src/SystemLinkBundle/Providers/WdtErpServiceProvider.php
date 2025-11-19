<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class WdtErpServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'SystemLinkBundle\Events\WdtErp\TradeFinishEvent' => [
            'SystemLinkBundle\Listeners\TradeFinishSendWdtErp', // 订单更新发送到旺店通
        ],
        'SystemLinkBundle\Events\WdtErp\TradeCancelEvent' => [
            'SystemLinkBundle\Listeners\TradeCancelSendWdtErp', // 取消订单发送到旺店通
        ],
        'SystemLinkBundle\Events\WdtErp\TradeAfterSaleEvent' => [
            'SystemLinkBundle\Listeners\TradeAfterSaleSendWdtErp', // 售后申请发送到旺店通
        ],
    ];
}
