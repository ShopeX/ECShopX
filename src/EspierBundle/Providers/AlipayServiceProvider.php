<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use PaymentBundle\Manager\AlipayManager;
use Illuminate\Support\ServiceProvider;

class AlipayServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('alipay.app.payment', function () {
            return new AlipayManager(config('alipay'));
        });

        $this->app->singleton('alipay.app.paymentH5', function () {
            return new AlipayManager(config('alipay'));
        });

        $this->app->singleton('alipay.app.paymentApp', function () {
            return new AlipayManager(config('alipay'));
        });

        $this->app->singleton('alipay.app.paymentPos', function () {
            return new AlipayManager(config('alipay'));
        });

        $this->app->singleton('alipay.app.paymentMini', function() {
            return new AlipayManager(config('alipay'));
        });
    }
}
