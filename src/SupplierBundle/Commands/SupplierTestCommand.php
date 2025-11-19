<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SupplierBundle\Commands;

use AdaPayBundle\Services\AdapayDrawCashService;
use AdaPayBundle\Services\MerchantService;
use AdaPayBundle\Services\SettleAccountService;
use AdaPayBundle\Services\SubMerchantService;
use Illuminate\Console\Command;

class SupplierTestCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'tools:supplier_test';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '供应商功能测试';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // CRC: 2367340174
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('finish');
        return true;
    }
}
