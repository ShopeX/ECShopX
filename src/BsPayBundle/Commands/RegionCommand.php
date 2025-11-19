<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
