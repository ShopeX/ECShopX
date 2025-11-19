<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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