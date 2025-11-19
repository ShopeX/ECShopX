<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
