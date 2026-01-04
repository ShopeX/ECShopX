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

namespace DistributionBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // 53686f704578
    protected $listen = [
        'DistributionBundle\Events\DistributorCreateEvent' => [
            'SystemLinkBundle\Listeners\ShopCreateSendOme',
        ],

        'DistributionBundle\Events\DistributorUpdateEvent' => [
            'SystemLinkBundle\Listeners\ShopUpdateSendOme',
        ],

        // 退货退款时可退运费，自动更新到自营店
        'DistributionBundle\Events\RefundFreightAutoZyEvent' => [
            'DistributionBundle\Listeners\RefundFreightAutoZyListener',
        ],

    ];
}
