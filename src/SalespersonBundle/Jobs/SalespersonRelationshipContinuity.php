<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;

class SalespersonRelationshipContinuity extends Job
{
    private $input;
    public function __construct($input)
    {
        $this->input = $input;
    }

    public function handle()
    {
        $request = new MarketingCenterRequest();
        $result = $request->call($this->input['company_id'], 'wxapp.action.event', $this->input);
        app('log')->debug('wxapp.action.event:'.var_export($result, 1));
    }
}
