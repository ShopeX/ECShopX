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
