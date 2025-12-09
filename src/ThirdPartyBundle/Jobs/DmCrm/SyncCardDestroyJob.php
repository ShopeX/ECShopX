<?php

namespace ThirdPartyBundle\Jobs\DmCrm;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\DmCrm\DiscountCardService as DmDiscountCardService;

class SyncCardDestroyJob extends Job 
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
            $dmDiscountCardService->syncCardDestroy($this->companyId, $this->msgContent);
        }
    }

}
