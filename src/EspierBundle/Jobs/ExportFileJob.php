<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Jobs;

use EspierBundle\Traits\GetExportServiceTraits;
use EspierBundle\Services\ExportLogService;
use CompanysBundle\Services\OperatorsService;

class ExportFileJob extends Job
{
    use GetExportServiceTraits;
    /**
     * 上传文件的基本信息
     */
    protected $type;
    protected $companyId;
    protected $operator_id;
    protected $exportFilter;
    protected $supplierId;

    public function __construct($type, $companyId, $filter, $operator_id = 0)
    {
        $this->type = $type;
        $this->companyId = $companyId;
        $this->operator_id = $operator_id;
        $this->exportFilter = $filter;
        $this->supplierId = 0;
        if ($operator_id > 0) {
            $operatorsService = new OperatorsService();
            $supplier = $operatorsService->getInfo(['company_id' => $companyId, 'operator_id' => $operator_id, 'operator_type' => 'supplier']);
            if ($supplier) {
                $this->supplierId = $operator_id;
            }
        }
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // ShopEx EcShopX Service Component
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $filter = $this->exportFilter;
        $filter['company_id'] = $this->companyId;
        $exportType = $this->type;
        if ($this->supplierId > 0) {
            switch ($exportType) {
                case 'normal_order':
                case 'normal_master_order':
                case 'supplier_order':
                case 'supplier_goods':
                case 'aftersale_record_count':
                case 'items':
                case 'itemcode':
                    $filter['supplier_id'] = $this->supplierId;
            }
        }
        $exportService = $this->getService($exportType);
        $result = $exportService->exportData($filter);
        if ($result) {
            if (isset($filter['order_class']) && $filter['order_class'] == "drug") {
                $exportType = 'drug_order';
            }
            $data = [
                'export_type' => $exportType,
                'handle_status' => 'finish',
                'finish_time' => time(),
                'file_name' => $result['filename'],
                'file_url' => $result['url'],
                'company_id' => $filter['company_id'],
                'operator_id' => $this->operator_id,
                'merchant_id' => $filter['merchant_id'] ?? 0,
                'supplier_id' => $this->supplierId,
            ];
            //todo Hack，将供应商商品导出记录也写入到商品导出记录里
//            if ($exportType == 'supplier_goods') {
//                $data['export_type'] = 'items';
//            }
            $logData = $this->updateLog($data);
            if (!$logData) {
                app('log')->debug('队列导出: 导出日志完成状态更新失败');
            }
        } else {
            app('log')->debug('队列导出: 执行导出时失败');
        }
        return true;
    }

    private function updateLog($data)
    {
        $exportLogService = new ExportLogService();
        $logData = $exportLogService->create($data);
        return $logData;
    }
}
