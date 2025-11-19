<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use OrdersBundle\Services\OrderInvoiceService;

class InvoiceRedQueryJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $jobData;

    /**
     * 任务可以尝试的最大次数
     */
    public $tries = 3;

    /**
     * 任务超时时间（秒）
     */
    public $timeout = 60;

    /**
     * 创建新的任务实例
     *
     * @param array $jobData
     * @return void
     */
    public function __construct($jobData)
    {
        $this->jobData = $jobData;
    }

    /**
     * 执行任务
     *
     * @return void
     */
    public function handle()
    {
        app('log')->info('[InvoiceRedQueryJob][handle] 开始处理红冲查询任务', $this->jobData);

        try {
            $companyId = $this->jobData['company_id'];
            $orderId = $this->jobData['order_id'];
            $invoiceId = $this->jobData['id'];

            // 创建发票服务实例
            $orderInvoiceService = new OrderInvoiceService();

            // 调用发票服务查询红票文件地址
            $queryResult = $orderInvoiceService->queryRedInvoice($this->jobData);

            app('log')->info('[InvoiceRedQueryJob][handle] 红冲查询结果', $queryResult);

        } catch (\Exception $e) {
            app('log')->error('[InvoiceRedQueryJob][handle] 红冲查询处理失败', [
                'job_data' => $this->jobData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 重新抛出异常，让队列重试
            throw $e;
        }
    }

    /**
     * 任务失败时的处理
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        app('log')->error('[InvoiceRedQueryJob][failed] 红冲查询任务最终失败', [
            'job_data' => $this->jobData,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // 可以在这里添加失败后的处理逻辑，比如发送通知等
    }
} 