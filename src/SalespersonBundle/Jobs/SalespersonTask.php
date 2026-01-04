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
