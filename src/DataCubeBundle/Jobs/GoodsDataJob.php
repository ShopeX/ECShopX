<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DataCubeBundle\Jobs;

use EspierBundle\Jobs\Job;
use DataCubeBundle\Services\GoodsDataService;
use EspierBundle\Services\ExportLogService;

class GoodsDataJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->data = $params;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $params = $this->data;
        $companyDataService = new GoodsDataService();
        $result = $companyDataService->exportData($params);
        //IT端可以导出全部品牌数据，company_id存储为0
        $company_id = !empty($params['company_id']) && $params['company_id'] != null ? $params['company_id'] : 0;
        if ($result) {
            $data = [
                'operator_id' => $params['operator_id'],
                'export_type' => "goods_data",
                'handle_status' => 'finish',
                'finish_time' => time(),
                'file_name' => $result['filename'],
                'file_url' => $result['url'],
                'company_id' => $company_id,
                'merchant_id' => 0,
                'supplier_id' => 0,
            ];
            if (isset($params['merchant_id'])) {
                $data['merchant_id'] = $params['merchant_id'];
            }
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
