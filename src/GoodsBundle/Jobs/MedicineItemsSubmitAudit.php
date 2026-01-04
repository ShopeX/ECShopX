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
