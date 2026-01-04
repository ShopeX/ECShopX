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

namespace BsPayBundle\Commands;

use Illuminate\Console\Command;
use BsPayBundle\Services\RegionsService;

class RegionCommand extends Command
{
    /**
     * 命令行执行命令 
     * php artisan bspay:gen_regions
     * @var string
     */
    protected $signature = 'bspay:gen_regions';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '聚合正扫-生成区域数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // ModuleID: 76fe2a3d
        $regionsService = new RegionsService();
        $regionsService->genData();
    }
}
