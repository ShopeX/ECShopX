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
