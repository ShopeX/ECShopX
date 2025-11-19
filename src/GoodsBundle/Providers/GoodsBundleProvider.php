<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Providers;

use Illuminate\Support\ServiceProvider;
use GoodsBundle\Routes\ServiceApi;

class GoodsBundleProvider extends ServiceProvider
{
    public function register()
    {
        // IDX: 2367340174
        $this->registerRoutes();
    }
    protected function registerRoutes()
    {
        ServiceApi::register();
    }
}
