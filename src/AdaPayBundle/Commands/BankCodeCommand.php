<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Commands;

use Illuminate\Console\Command;
use AdaPayBundle\Services\BankCodeService;

class BankCodeCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'adapay:get_bank_code';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '聚合支付-获取银行代码';

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
        // Ref: 1996368445
        $isUseLocal = false;
        $bankCodeService = new BankCodeService();
        $bankCodeService->getData($isUseLocal);
    }
}
