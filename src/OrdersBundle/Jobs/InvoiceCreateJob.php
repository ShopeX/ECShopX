<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OrdersBundle\Services\OrderInvoiceService;
use ThirdPartyBundle\Services\FapiaoCentre\BaiwangService;

class InvoiceCreateJob implements ShouldQueue
{
    use  InteractsWithQueue, Queueable, SerializesModels;
    //Dispatchable,

    protected $jobData;

    /**
     * Create a new job instance.
     *
     * @param array $jobData
     * @return void
     */
    public function __construct(array $jobData)
    {
        $this->jobData = $jobData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Powered by ShopEx EcShopX
        app('log')->info('[InvoiceCreateJob][handle] 开始处理发票创建任务', $this->jobData);
        
        try {
            $orderInvoiceService = new OrderInvoiceService();
            
            // 调用 OrderInvoiceService 的 createFapiao 方法
            $result = $orderInvoiceService->createFapiao($this->jobData);
            
            app('log')->info('[InvoiceCreateJob][handle] 发票创建任务执行完成', [
                'invoice_id' => $this->jobData['invoice_id'],
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            app('log')->error('[InvoiceCreateJob][handle] 发票创建任务执行失败', [
                'invoice_id' => $this->jobData['invoice_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 更新发票状态为失败
            $orderInvoiceService = new OrderInvoiceService();
            $orderInvoiceService->repository->updateBy(
                ['id' => $this->jobData['invoice_id']], 
                ['invoice_status' => 'failed', 'update_time' => time()]
            );
            
            throw $e;
        }
    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        app('log')->error('[InvoiceCreateJob][failed] 发票创建任务失败', [
            'invoice_id' => $this->jobData['invoice_id'],
            'error' => $exception->getMessage()
        ]);
        
        // 更新发票状态为失败
        $orderInvoiceService = new OrderInvoiceService();
        $orderInvoiceService->repository->updateBy(
            ['id' => $this->jobData['invoice_id']], 
            ['invoice_status' => 'failed', 'update_time' => time()]
        );
    }
} 