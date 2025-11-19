<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;

class BindSalseperson extends Job
{
    private $companyId;
    private $unionid;
    private $workUserid;
    private $customerType;
    public function __construct($companyId, $unionid, $workUserid, $customerType)
    {
        $this->companyId = $companyId;
        $this->unionid = $unionid;
        $this->workUserid = $workUserid;
        $this->customerType = $customerType;
    }

    public function handle()
    {
        if (!$this->companyId || !$this->unionid || !$this->workUserid || !$this->customerType) {
            return false;
        }

        $params = [
            'company_id' => $this->companyId,
            'unionid' => $this->unionid,
            'gu_user_id' => $this->workUserid,
            'customer_type' => (string) $this->customerType,
        ];
        app('log')->debug('salesperson.bind.member:params===>'.var_export($params, 1));
        $request = new MarketingCenterRequest();
        $result = $request->call($this->companyId, 'salesperson.bind.member', $params);
        app('log')->debug('salesperson.bind.member:result===>'.var_export($result, 1));
    }
}
