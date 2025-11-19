<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
