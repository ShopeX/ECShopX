<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use CompanysBundle\Ego\CompanysActivationEgo;

class AuthorizationActivationProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('authorization', function () {
            return new CompanysActivationEgo();
        });
    }
}
