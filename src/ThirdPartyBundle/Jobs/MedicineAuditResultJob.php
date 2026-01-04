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

namespace ThirdPartyBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\Kuaizhen580Center\Src\GoodsService;

class MedicineAuditResultJob extends Job
{
    public $data;

    public function __construct($params)
    {
        $this->data = $params;
    }

    public function handle()
    {
        $params = $this->data;

        if (empty($params)) {
            return true;
        }
        $auditResult = [
            'medicineId' => $params['medicineIds'],
            'auditStatus' => $params['errCode'] == 0 ? 1: 0, // errCode为0审核通过，其他为审核不通过
            'auditMsg' => $params['errMsg'],
        ];
        $kzGoodsService = new GoodsService();
        $result = $kzGoodsService->updateMedicineAuditResult($auditResult);

        return $result;
    }
}
