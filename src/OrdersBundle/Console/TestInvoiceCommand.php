<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Console;

use Illuminate\Console\Command;
use OrdersBundle\Services\OrderInvoiceService;
use ThirdPartyBundle\Services\FapiaoCentre\BaiwangService;

class TestInvoiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:invoice {action} {--params=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试发票功能';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $params = $this->option('params');
        
        if ($params) {
            $params = json_decode($params, true);
        }
        
        $this->info("执行发票测试: {$action}");
        
        try {
            switch ($action) {
                case 'create':
                    $this->testCreateInvoice($params);
                    break;
                case 'query':
                    $this->testQueryInvoice($params);
                    break;
                case 'schedule':
                    $this->testSchedule();
                    break;
                default:
                    $this->error("未知的操作: {$action}");
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("测试失败: " . $e->getMessage());
            $this->error("堆栈跟踪: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * 测试创建发票
     */
    private function testCreateInvoice($params)
    {
        $this->info("测试创建发票...");
        
        $orderInvoiceService = new OrderInvoiceService();
        $result = $orderInvoiceService->createFapiao($params);
        
        $this->info("创建发票结果: " . json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 测试查询发票
     */
    private function testQueryInvoice($data)
    {
        $this->info("测试查询发票...");
        $orderInvoiceService = new OrderInvoiceService();
        $invoice = $orderInvoiceService->getInfo(['order_id' => $data['order_id']]);
        //47>$  ./artisan  test:baiwang query --params='{"company_id":1,"order_id":"4930601000310028","invoiceCode":"4930601000310028","serialNos":"25070118551097000363"}'
        $params = [
            'invoice_id' => $invoice['id'],
            'company_id' => $invoice['company_id'],
            'invoice_apply_bn' => $invoice['invoice_apply_bn'],
            'serialNos' => $invoice['invoice_apply_bn'],
            'orderNos' => [$invoice['order_id'] . '-' . $invoice['id']],
            'order_id' => $invoice['order_id'],
            'detailMark' => '1'
        ];
        $this->info("查询发票参数: " . json_encode($params, JSON_UNESCAPED_UNICODE));
        
        $result = $orderInvoiceService->queryInvoice($params);
        
        
        $this->info("查询发票结果: " . json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 测试定时任务
     */
    private function testSchedule()
    {
        $this->info("测试定时任务...");
        
        $orderInvoiceService = new OrderInvoiceService();
        
        $this->info("执行定时开票任务...");
        $orderInvoiceService->invoiceStartSchedule();
        
        $this->info("执行定时查询任务...");
        $orderInvoiceService->queryInvoiceSchedule();
        
        $this->info("定时任务执行完成");
    }
} 