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

namespace ThirdPartyBundle\Commands;

use Illuminate\Console\Command;
use ThirdPartyBundle\Services\FapiaoCentre\BaiwangService;

class TestBaiwangCommand extends Command
{
    /**
     * 命令行名称
     *
     * @var string
     */
    protected $signature = 'test:baiwang {action} {--params=}';

    /**
     * 命令行描述 
     *
     * @var string
     */
    protected $description = '测试百旺云发票相关功能（token、开发票、查票等）';

    public function handle()
    {
        $action = $this->argument('action');
        $params = $this->option('params') ? json_decode($this->option('params'), true) : [];
        $service = new BaiwangService();

        switch ($action) {
            case 'token':
                $token = $service->getToken();
                $this->info('Token: ' . $token);
                break;
            case 'create':
                //params data
                $params_test = [
                    'company_id' => 1,
                    'order_id' => 0,
                ];
                // json encode
                $params_test = json_encode($params_test);
                var_dump("sample params_test: ",$params_test);
                var_dump("sample params: ",$params);
                if( !$params['order_id']){
                    $this->error('  和 order_id 不能为空');
                    exit;
                }
                $result = $service->createFapiao($params);
                $this->info('开发票结果: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'query':
                // 支持传递 taxNo、serialNos、orderNos、detailMark
                // $queryParams = [];
                // if (isset($params['order_id'])) {
                //     $queryParams['order_id'] = $params['order_id'];
                // }                
                // if (isset($params['taxNo'])) {
                //     $queryParams['taxNo'] = $params['taxNo'];
                // }
                // if (isset($params['serialNos'])) {
                //     $queryParams['serialNos'] = is_array($params['serialNos']) ? $params['serialNos'] : explode(',', $params['serialNos']);
                // }
                // if (isset($params['orderNos'])) {
                //     $queryParams['orderNos'] = is_array($params['orderNos']) ? $params['orderNos'] : explode(',', $params['orderNos']);
                // }
                // if (isset($params['detailMark'])) {
                //     $queryParams['detailMark'] = $params['detailMark'];
                // }
                $result = $service->queryInvoice($params);
                $this->info('查票结果: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'cancel':
                $result = $service->cancelInvoice($params['invoiceCode'] ?? '', $params['invoiceNo'] ?? '', $params['reason'] ?? '');
                $this->info('作废结果: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'red':
                $this->info('冲红参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
                $result = $service->redInvoice($params);
                $this->info('红票结果: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'redjob':
                $this->info('冲红参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
                //退款成功推发票冲红
                dispatch(new \OrdersBundle\Jobs\InvoiceRedJob($params))->onQueue('invoice');
                $this->info('发票冲红任务已提交');
                break;
            case "redQuery":
                $result = $service->queryRedConfirm($params);
                $this->info('红票查询结果: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case "redQueryJob":
                $this->info('红票查询任务参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
                // 分发红票查询任务
                dispatch(new \OrdersBundle\Jobs\InvoiceRedQueryJob($params))->onQueue('invoice');
                $this->info('红票查询任务已提交');
                break;
            case 'quota':
                $result = $service->queryInvoiceQuota();
                $this->info('额度查询: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'email':
                $result = $service->sendInvoiceEmail($params['invoiceCode'] ?? '', $params['invoiceNo'] ?? '', $params['email'] ?? '');
                $this->info('发票邮件发送: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'sms':
                $result = $service->sendInvoiceSms($params['invoiceCode'] ?? '', $params['invoiceNo'] ?? '', $params['mobile'] ?? '');
                $this->info('发票短信发送: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'pdf':
                $result = $service->downloadInvoicePdf($params['invoiceCode'] ?? '', $params['invoiceNo'] ?? '');
                $this->info('PDF下载: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'qualification':
                $result = $service->queryQualification();
                $this->info('资质查询: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            case 'device':
                $result = $service->queryDeviceStatus();
                $this->info('设备状态: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                break;
            default:
                $this->error('未知 action。支持: token, create, query, cancel, red, quota, email, sms, pdf, qualification, device');
        }
    }
} 