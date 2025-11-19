<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ShuyunBundle\Jobs;

use EspierBundle\Jobs\Job;
use ShuyunBundle\Services\MembersService as ShuyunMembersService;

class MemberRegisterJob extends Job
{
    protected $companyId;
    protected $userId;
    protected $params;

    public function __construct($companyId, $userId, $params)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->params = $params;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        app('log')->info('file:'.__FILE__.',line:'.__LINE__.',companyId:'.$this->companyId.',userId:'.$this->userId);
        app('log')->info('file:'.__FILE__.',line:'.__LINE__.',params====>'.var_export($this->params, true));
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $shuyunMembersService = new ShuyunMembersService($this->companyId, $this->userId);
        $shuyunMembersService->memberRegister($this->params);
        
        return true;
    }
}
