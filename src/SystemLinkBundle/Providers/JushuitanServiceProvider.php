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
