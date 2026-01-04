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

namespace KaquanBundle\Jobs;

use EspierBundle\Jobs\Job;
use KaquanBundle\Services\PackageReceivesService;

class ReceivesCardPackage extends Job
{
    public $companyId;
    public $userId;
    public $packageId;
    public $receiveId;
    public $from;
    public $salespersonId;


    public function __construct(int $companyId, int $userId, int $packageId, int $receiveId, string $from, int $salespersonId = 0)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->packageId = $packageId;
        $this->receiveId = $receiveId;
        $this->from = $from;
        $this->salespersonId = $salespersonId;
    }


    public function handle()
    {
        try {
            (new PackageReceivesService())->sendCouponsToUsers($this->companyId, $this->userId, $this->packageId, $this->receiveId, $this->from, $this->salespersonId);
        } catch (\Exception $e) {
            app('log')->error('卡券包发放Job error =>:' . $e->getMessage() . PHP_EOL);
        }
    }
}
