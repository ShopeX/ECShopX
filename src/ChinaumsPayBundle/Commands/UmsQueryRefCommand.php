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

namespace ChinaumsPayBundle\Commands;

use Illuminate\Console\Command;
use ChinaumsPayBundle\Services\UmsService;

class UmsQueryRefCommand extends Command
{
    
    protected $signature = 'ums:query-refund';
    
    protected $description = '银联查询退款';
    
    public function handle()
    {
        $result = [];
        try {
            $result = (new UmsService)->tsUmsQueryRefs();
        } catch (\Exception $e) {
             app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage());
        }
        echo("result:\n". json_encode($result, 1));
        return true;
    }
}