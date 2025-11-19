<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\Kuaizhen580Center\Src\GoodsService;

class MedicineItemsSubmitAudit extends Job
{
    protected $data = [];
    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($data)
    {
        // Log: 456353686f7058
        $this->data = $data;
    }

    public function handle()
    {
        // Log: 456353686f7058
        $itemsData = [
            [
                'medicine_type' => $this->data['medicine_data']['medicine_type'],
                'common_name' => $this->data['medicine_data']['common_name'],
                'name' => $this->data['item_name'],
                'dosage' => $this->data['medicine_data']['dosage'],
                'spec' => $this->data['medicine_data']['spec'],
                'packing_spec' => $this->data['medicine_data']['packing_spec'],
                'manufacturer' => $this->data['medicine_data']['manufacturer'],
                'approval_number' => $this->data['medicine_data']['approval_number'],
                'unit' => $this->data['medicine_data']['unit'],
                'item_id' => $this->data['item_id'],
                'bar_code' => $this->data['barcode'],
                'is_prescription' => $this->data['medicine_data']['is_prescription'],
                'price' => $this->data['price'],
                'stock' => '',
                'special_common_name' => $this->data['medicine_data']['special_common_name'],
                'special_spec' => $this->data['medicine_data']['special_spec'],
            ]
        ];
        $service = new GoodsService();

        try {
            $result = $service->medicineSync($this->data['company_id'], $itemsData);
        } catch (\Exception $exception) {
            $auditResult = [
                'medicineId' => $this->data['item_id'],
                'auditStatus' => 0, // 0审核不通过，其他为审核通过
                'auditMsg' => $exception->getMessage(),
            ];
            $kzGoodsService = new GoodsService();
            $kzGoodsService->updateMedicineAuditResult($auditResult);

            app('log')->debug('MedicineItemsSubmitAudit-->>e:' . $exception->getMessage());
            return true;
        }

        app('log')->debug('MedicineItemsSubmitAudit-->>res:' . json_encode($result));

        return true;
    }
}
