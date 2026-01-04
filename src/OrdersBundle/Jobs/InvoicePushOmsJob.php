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

use EspierBundle\Jobs\Job;
use OrdersBundle\Entities\NormalOrdersItems;
use ThirdPartyBundle\Services\OmsApiService;
use OrdersBundle\Services\OrderInvoiceService;
use ThirdPartyBundle\Services\OmsSettingService;

class InvoicePushOmsJob extends Job
{
    protected $invoice_id;
    protected $company_id;

    /**
     * 创建一个新的任务实例。
     *
     * @param int $invoice_id 发票ID
     * @param int $company_id 公司ID
     * @return void
     */
    public function __construct($invoice_id, $company_id)
    {
        $this->invoice_id = $invoice_id;
        $this->company_id = $company_id;
    }

    /**
     * 运行任务。
     *
     * @return void
     */
    public function handle()
    {
        // 记录任务开始执行日志
        app('log')->info('InvoicePushOmsJob: 开始执行发票推送OMS任务');

        $omsSettingService = new OmsSettingService();
        $config = $omsSettingService->getSetting($this->company_id);
        $host = $config['api_host'] ?? '';
        $nodeId = $config['node_id'] ?? '';
        $appSecret = $config['app_secret'] ?? '';
        if(!$host || !$nodeId || !$appSecret) {
            app('log')->error('InvoicePushOmsJob: 未配置OMS对接参数');
            return false;
        }
        try {

            // 获取发票信息和商品明细
            $invoiceService = new OrderInvoiceService();
            $invoiceInfo = $invoiceService->getInvoiceDetail($this->invoice_id, $this->company_id);

            if (empty($invoiceInfo)) {
                app('log')->error('InvoicePushOmsJob: 未找到发票信息');
                return false;
            }


            // 构建OMS接口请求参数
            $invoiceParams = $this->buildInvoiceParams($invoiceInfo);
            app('log')->info('InvoicePushOmsJob: 推送OMS开始 invoiceParams:'.json_encode($invoiceParams));
            // 调用OMS API服务
            $omsApiService = new OmsApiService($this->company_id);
            $response = $omsApiService->callApi('ome.invoice.apply', $invoiceParams,$this->company_id);
            app('log')->info('InvoicePushOmsJob: 推送OMS结束 response:'.json_encode($response));

            // 处理响应结果
            $this->handleResponse($response, $invoiceInfo);

            return true;
        } catch (\Exception $e) {
            app('log')->error('InvoicePushOmsJob执行异常: ' . $e->getMessage());

            return false;
        }
    }



    /**
     * 构建发票推送OMS所需的参数
     *
     * @param array $invoiceData 发票数据
     * @return array
     */
    private function buildInvoiceParams(array $invoiceData)
    {
        $invoice = $invoiceData;
        $items = $invoiceData['invoice_items'];

        // 构建订单明细
        $orderItems = [];
        foreach ($items as $item) {
            $orderItems[] = [
                'invoice_apply_bn' => $invoice['invoice_apply_bn'] ?? '',
                'order_bn' => $item['order_id'] ?? '',  // 使用order_id作为order_bn
                'oid' => $item['oid'] ?? '',       // 使用order_id作为oid
                'bn' => $item['item_bn'] ?? '',
                'amount' => $item['amount'] ?? '0',
                'num' => $item['num'] ?? '1',
                'tax_type' => $invoice['invoice_type'] ?? 'company',
                'tax_title' => $invoice['company_title'] ?? '',
                'tax_no' => $invoice['company_tax_number'] ?? '',
                'tax_mobile' => $invoice['mobile'] ?? '',
                'tax_email' => $invoice['email'] ?? ''
            ];
        }

        $params['invoice_items'] = json_encode($orderItems, JSON_UNESCAPED_UNICODE);

        return $params;
    }

    /**
     * 处理OMS响应结果
     *
     * @param array $response OMS响应结果
     * @param array $invoiceData 发票数据
     * @return void
     */
    private function handleResponse(array $response, array $invoiceData)
    {
        app('log')->info('InvoicePushOmsJob: OMS响应结果');

        // 检查响应是否成功
        if (isset($response['rsp']) && $response['rsp'] === 'succ') {
            app('log')->info('InvoicePushOmsJob: OMS响应结果:成功');
            $invoiceService = new OrderInvoiceService();
            $updateData = [
                'is_oms' => 1,
                'invoice_status' => 'inProgress',
            ];

            // 更新推送结果
            $result = $invoiceService->updateInvoice($invoiceData['id'], $updateData);

            $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);

            foreach ($invoiceData['invoice_items'] as $item) {
                if(!$item['oid']){
                    continue;
                }
                $normalOrdersItemsRepository->update(
                    ['company_id' => $item['company_id'], 'order_id' => $item['order_id'],'oid' => $item['oid']],
                    ['is_invoice' => 2]
                );
            }
        } else {
            // 失败处理逻辑
            $errorMsg = $response['msg'] ?? '未知错误';
            app('log')->info('InvoicePushOmsJob: OMS响应结果:失败，原因：'.$errorMsg);
        }
    }


}
