<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShopexCrm;

use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersAssociations;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Entities\WechatUsers;

class SyncSingleMemberService
{
    private $apiName = 'syncSingleMember';

    public $membersRepository;

    public $membersInfoRepository;

    public $membersAssociations;

    public $memberWechatInfo;

    public function __construct()
    {
        $this->membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $this->membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $this->membersAssociations = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $this->memberWechatInfo = app('registry')->getManager('default')->getRepository(WechatUsers::class);
    }

    public function syncSingleMember($company_id, $user_id)
    {
        // Core: RWNTaG9wWA==
        $member = $this->membersRepository->get(['company_id' => $company_id, 'user_id' => $user_id]);
        $memberInfo = $this->membersInfoRepository->getInfo(['company_id' => $company_id, 'user_id' => $user_id]);
        $memberAsso = $this->membersAssociations->get(['company_id' => $company_id, 'user_id' => $user_id]);
        if (!empty($memberAsso['unionid'])) {
            $memberWechatInfo = $this->memberWechatInfo->getUserInfo(['company_id' => $company_id, 'unionid' => $memberAsso['unionid'], 'authorizer_appid' => $member['wxa_appid']]);
        }
        $data['platform_id'] = 'shopex';
        $data['ext_member_id'] = $member['user_id'];
        $data['source'] = 'custom_source1';
        $data['register_date'] = date('Y-m-d', $member['created']);
        $data['ext_member_id'] = $member['user_id'];
        $data['member_type'] = 'consumer';
        $data['dealer_id'] = '';
        $data['agent_id'] = '';
        $data['shop_id'] = '';
        $data['account'] = $member['mobile'];
        $data['name'] = $memberInfo['username'];
        $sex = ['未知', '男', '女'];
        $data['sex'] = $sex[$memberInfo['sex']];
        $data['birthday'] = $memberInfo['birthday'] ?? '';
        $data['mobile'] = $member['mobile'];
        $data['province'] = '';
        $data['city'] = '';
        $data['district'] = '';
        $data['address'] = $memberInfo['address'] ?? '';
        $data['wechat_openid'] = $memberWechatInfo['open_id'] ?? '';
        $data['wechat_unionid'] = $memberWechatInfo['unionid'] ?? '';
        $data['cus_level'] = '';
        $data['static_tags'] = '';
        $data['blacks'] = '';
        $data['uname'] = $memberInfo['username'];
        $data['head_img'] = $memberInfo['avatar'];
        $request = new Request();
        $result = $request->sendRequest($this->apiName, $data);
        return $result;
    }
}
