<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
