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

namespace OrdersBundle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
use OrdersBundle\Services\OrderInvoiceService;

class InvoiceRedJob implements ShouldQueue
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
        // XXX: review this code
        $this->jobData = $jobData;
    }

    /**
     * 执行任务
     *
     * @return void
     */
    public function handle()
    {
        app('log')->info('[InvoiceRedJob][handle] 开始处理发票冲红任务', $this->jobData);

        try {
            $companyId = $this->jobData['company_id'];
            $orderId = $this->jobData['order_id'];

            // 创建发票服务实例
            $orderInvoiceService = new OrderInvoiceService();
            // getInfo
            $filter = [
                'order_id' => $orderId,
            ];
            $invoice = $orderInvoiceService->getInfo($filter);
            app('log')->info('[InvoiceRedJob][handle] 发票信息', $invoice);
            if ($invoice['invoice_status'] == 'cancel') {
                app('log')->info('[InvoiceRedJob][handle] 发票已取消', $invoice);
                return;
            }
            if ($invoice['invoice_status'] == 'pending') {
                //取消发票
                $orderInvoiceService->updateInvoice($invoice['id'], ['invoice_status' => 'cancel']);
                app('log')->info('[InvoiceRedJob][handle] 发票已取消', $invoice);
                return;
            }
            if ($invoice['invoice_status'] == 'success') {
                //冲红
                $queryResult = $orderInvoiceService->redInvoice($this->jobData);
                app('log')->info('[InvoiceRedJob][handle] 冲红结果', $queryResult);
                return;
            }


            // 处理查询结果
            // $orderInvoiceService->handleQueryResult($queryResult, $invoiceId);

            // app('log')->info('[InvoiceQueryJob][handle] 发票查询任务处理完成', [
            //     'invoice_id' => $invoiceId
            // ]);

        } catch (\Exception $e) {
            app('log')->error('[InvoiceQueryJob][handle] 发票冲红处理失败', [
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
        // XXX: review this code
        app('log')->error('[InvoiceQueryJob][failed] 发票查询任务最终失败', [
            'job_data' => $this->jobData,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // 可以在这里添加失败后的处理逻辑，比如发送通知等
    }
} 