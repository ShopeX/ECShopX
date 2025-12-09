<?php

namespace ThirdPartyBundle\Jobs\DmCrm;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\DmCrm\MemberService;

class SyncIntegralChangeJob extends Job 
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
       $memberService = new MemberService();
       $memberService->syncIntegralChange($this->companyId, $this->msgContent);
    }

}