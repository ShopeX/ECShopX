<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;

class SalespersonTask extends Job
{
    private $companyId;
    private $subtaskId;
    private $storeBn;
    private $employeeNumber;
    private $itemId;
    private $unionId;
    public function __construct($companyId, $subtaskId, $storeBn, $employeeNumber, $itemId, $unionId)
    {
        $this->companyId = $companyId;
        $this->subtaskId = $subtaskId;
        $this->storeBn = $storeBn;
        $this->employeeNumber = $employeeNumber;
        $this->itemId = $itemId;
        $this->unionId = $unionId;
    }

    public function handle()
    {
        if (!$this->companyId || !$this->subtaskId || !$this->storeBn || !$this->employeeNumber || !$this->unionId) {
            return false;
        }

        $params = [
            'company_id' => $this->companyId,
            'subtask_id' => $this->subtaskId,
            'store_bn' => $this->storeBn,
            'employee_number' => $this->employeeNumber,
            'user_id' => $this->unionId,
        ];

        if ($this->itemId) {
            $params['item_id'] = $this->itemId;
        }

        $request = new MarketingCenterRequest();
        $result = $request->call($this->companyId, 'tasks.tasks.complete', $params);
        app('log')->debug('tasks.tasks.complete:'.var_export($result, 1));
    }
}
