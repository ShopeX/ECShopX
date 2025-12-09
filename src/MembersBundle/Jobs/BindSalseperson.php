<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;
use ThirdPartyBundle\Services\DmCrm\MemberService as DmMemberService;
use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;
use WorkWechatBundle\Entities\WorkWechatRel;

class BindSalseperson extends Job
{
    private $companyId;
    private $unionid;
    private $workUserid;
    private $customerType;
    private $mobile;
    private $user_id;
    public function __construct($companyId, $unionid, $workUserid, $customerType, $mobile = '', $user_id=0)
    {
        $this->companyId = $companyId;
        $this->unionid = $unionid;
        $this->workUserid = $workUserid;
        $this->customerType = $customerType;
        $this->mobile = $mobile;
        $this->user_id = $user_id;
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

        $dmMemberService = new DmMemberService($this->companyId);
        $code = (int)($result['code'] ?? 0);
        if ($dmMemberService->isOpen && $code == 200 ) {
            $params['work_userid'] = $this->workUserid;
            $store = $request->call($this->companyId, 'basics.salesperson.relShop', $params)['data'] ?? [];
            $distributorInfo = $store[0] ?? [];
            $dmMemberService->updateMemberInfoByMobile([
                'mobile' => $this->mobile,
                'mainClerkCode' => $this->workUserid,
                'mainStoreCode' => $distributorInfo['shop_code'] ?? '',
            ]);
        }

        $code = (int)($result['code'] ?? 0);
        if ( $code != 200 ) {
            $salespersonService = new SalespersonService();
            $filter = [
                'company_id' => $this->companyId,
                'work_userid' => $this->workUserid,
                'salesperson_type' => 'shopping_guide',
                'is_valid' => 'true',
            ];
            $salespersonInfo = $salespersonService->getSalespersonDetail($filter);
            if ( $salespersonInfo ?? '' ) {
                $filter = [
                    'user_id' => $this->user_id,
                    'company_id' => $this->companyId,
                    'salesperson_id' => $salespersonInfo['salesperson_id'],
                ];
                $workWechatRepositories = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
                $isBound = $workWechatRepositories->getInfo($filter);
                if ( $isBound ) {
                    $filter = [
                        'id' => $isBound['id']
                    ];
                    $workWechatRepositories->updateBy($filter, ['is_bind' => 0]); // ½â°ó
                }
            }
        }
    }
}
