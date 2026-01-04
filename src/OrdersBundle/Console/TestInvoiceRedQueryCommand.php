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

namespace OrdersBundle\Console;

use Illuminate\Console\Command;
use OrdersBundle\Services\OrderInvoiceService;

class TestInvoiceRedQueryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:invoice-red-query {--company_id=1 : 公司ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试红冲定时查询功能';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $companyId = (int) $this->option('company_id');
        
        $this->info('开始测试红冲定时查询功能...');
        $this->info('公司ID: ' . $companyId);
        
        try {
            $orderInvoiceService = new OrderInvoiceService();
            
            // 执行红冲定时查询任务
            $orderInvoiceService->invoiceRedQuerySchedule();
            
            $this->info('红冲定时查询任务执行完成');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('红冲定时查询任务执行失败: ' . $e->getMessage());
            app('log')->error('[TestInvoiceRedQueryCommand][handle] 红冲定时查询任务执行失败', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
} 