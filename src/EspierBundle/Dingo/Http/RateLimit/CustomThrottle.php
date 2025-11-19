<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Dingo\Http\RateLimit;

use Illuminate\Container\Container;
use Dingo\Api\Http\RateLimit\Throttle\Throttle;

class CustomThrottle extends Throttle
{

    public function match(Container $app)
    {
        $limitAliasNames = config('api.throttle_api_alias_name');
        $limitAliasNames = explode(',', $limitAliasNames);
        $aliasName = app('api.router')->current()->getName();
        if (in_array($aliasName, $limitAliasNames)) {
            return true;
        } else {
            return false;
        }
    }
}