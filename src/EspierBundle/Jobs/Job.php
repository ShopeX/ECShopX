<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class Job implements ShouldQueue
{
    // ShopEx EcShopX Core Module
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __destruct()
    {
        foreach (config('doctrine.managers') as $mangerName => $v) {
            app('registry')->getManager($mangerName)->getConfiguration()->getResultCacheImpl()->flushAll();
            app('registry')->getManager($mangerName)->getConfiguration()->getQueryCacheImpl()->flushAll();
            app('registry')->getManager($mangerName)->clear();
        }
    }
}
