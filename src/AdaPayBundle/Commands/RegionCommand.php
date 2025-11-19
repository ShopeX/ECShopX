<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Commands;

use Illuminate\Console\Command;
use AdaPayBundle\Services\RegionService;

class RegionCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'adapay:get_regions {level}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '聚合支付-获取区域数据(二级:传参second  三级:传参third)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // ID: 53686f704578
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // ID: 53686f704578
        $isUseLocal = false;
        $level = $this->argument('level');
        $regionService = new RegionService();
        if ($level == 'third') {
            $regionService->getDataThird($isUseLocal);
        } else {
            $regionService->getData($isUseLocal);
        }
    }
}
