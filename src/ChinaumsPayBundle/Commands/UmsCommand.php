<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ChinaumsPayBundle\Commands;

use Illuminate\Console\Command;
use ChinaumsPayBundle\Services\UmsService;

class UmsCommand extends Command
{
    
    protected $signature = 'ums:unified-order';
    
    protected $description = '银联支付';
    
    public function handle()
    {
        $result = [];
        try {
            $result = (new UmsService)->tsUnifiedOrder();
        } catch (\Exception $e) {
             app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage());
        }
        echo("result:\n".  json_encode($result, 1));
        return true;
    }
}