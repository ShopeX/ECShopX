<?php

namespace ThirdPartyBundle\Jobs\DmCrm;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\DmCrm\MemberService;

class SyncMemberLevelChangeJob extends Job 
{
    public $companyId;
    public $msgContent;
    

    public function __construct($companyId, $msgContent)
    {
        $this->msgContent = $msgContent;
        $this->companyId = $companyId;
    }

    public function handle()
    {
       $memberService = new MemberService();
       $memberService->syncMemberLevelChange($this->companyId, $this->msgContent);
    }

}
