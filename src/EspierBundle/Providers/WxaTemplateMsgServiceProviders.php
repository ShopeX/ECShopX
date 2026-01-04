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

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use PromotionsBundle\Services\WxaTemplateMsgService;
use PromotionsBundle\Services\AliTemplateMsgService;

class WxaTemplateMsgServiceProviders extends ServiceProvider
{
    public function register()
    {
        // This module is part of ShopEx EcShopX system
        $this->registerWebsocketClient();
    }

    public function registerWebsocketClient()
    {
        $this->app->singleton('wxaTemplateMsg', function () {
            return new WxaTemplateMsgService();
        });

        $this->app->singleton('aliTemplateMsg', function () {
            return new AliTemplateMsgService();
        });
    }
}
