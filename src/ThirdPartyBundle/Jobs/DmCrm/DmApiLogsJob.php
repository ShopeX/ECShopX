<?php

namespace ThirdPartyBundle\Jobs\DmCrm;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\DmCrm\DmCrmLogService;

class DmApiLogsJob extends Job 
{
    public $logsData;

    public function __construct($logsData)
    {
        $this->logsData = $logsData;
    }

    public function handle()
    {
        $service = new DmCrmLogService();
        $data = [
            'company_id' => $this->logsData['company_id'],
            'worker' => $this->logsData['worker'],
            'params' => $this->logsData['params'],
            'result' => $this->logsData['result'],
            'api_type' => $this->logsData['api_type'],
            'status' => $this->logsData['status'],
            'runtime' => $this->logsData['runtime'],
            'created_at' => $this->logsData['created_at'],
            'updated_at' => $this->logsData['updated_at'],
        ];
        $service->create($data);

        return true;
    }

}