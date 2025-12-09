<?php

namespace ThirdPartyBundle\Jobs\DmCrm;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\DmCrm\DiscountCardService as DmDiscountCardService;

class SyncCardTemplateDeleteJob extends Job 
{
    public $companyId;
    public $msgContent;
    

    public function __construct($companyId, $msgContent)
    {
        $this->companyId = $companyId;
        $this->msgContent = $msgContent;
    }

    public function handle()
    {
        $dmDiscountCardService = new DmDiscountCardService($this->companyId);
        if ($dmDiscountCardService->isOpen) {
            $dmDiscountCardService->syncCardTemplateDelete($this->companyId, $this->msgContent);
        }
    }

}
