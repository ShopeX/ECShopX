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
