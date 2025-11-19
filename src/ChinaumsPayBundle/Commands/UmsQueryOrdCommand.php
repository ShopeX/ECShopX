<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ChinaumsPayBundle\Commands;

use Illuminate\Console\Command;
use ChinaumsPayBundle\Services\UmsService;

class UmsQueryOrdCommand extends Command
{
    
    protected $signature = 'ums:query';
    
    protected $description = '银联查询订单';
    
    public function handle()
    {
        $result = [];
        try {
            $result = (new UmsService)->tsUmsQueryOrd();
        } catch (\Exception $e) {
             app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage());
        }
        echo("result:\n". json_encode($result, 1));
        return true;
    }
}