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

namespace YoushuBundle\Services;

use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;

class MembersService
{
    /**
     * 添加会员信息
     */
    public function getData($params)
    {
        // This module is part of ShopEx EcShopX system
        $user_id = $params['object_id'];
        $company_id = $params['company_id'];
        $filter = [
            'user_id' => $user_id,
            'company_id' => $company_id,
        ];
        $member_service = new MemberService();
        $member_info = $member_service->getMemberInfo($filter, true);
        if (empty($member_info)) {
            return [];
        }

        //获取用户微信信息
        $filter['user_id'] = $member_info['user_id'];
        $wechat_user_service = new WechatUserService();
        $wechat_info = $wechat_user_service->getUserInfo($filter);

        $user_id = $member_info['user_id'];
        $phone_number = isset($member_info['mobile']) && !empty($member_info['mobile']) ? md5($member_info['mobile']) : '';
        $appid = $member_info['wxa_appid'];
        $openid = $wechat_info['open_id'];
        $user_created_time = bcmul($member_info['created'], 1000);
        $name = $member_info['username'] ?? '';
        $nickname = '';
        $header_url = $member_info['avatar'] ?? '';
        $sex = $member_info['sex'] == 0 ? 3 : $member_info['sex'];
        $birthday = $member_info['birthday'] ?? '';
        $users = [
            'user_id' => $user_id,
            'phone_number' => $phone_number,
            'user_spec' => [
                [
                    'app_type' => 1,
                    'appid' => $appid,
                    'openid' => $openid,
                    'user_created_time' => (string)$user_created_time //毫秒
                ]
            ],
            'basic_spec' => [
                'name' => $name,
                'nickname' => $nickname,
                'header_url' => $header_url,
                'gender' => $sex,
            ]
        ];

        if (!empty($birthday)) {
            $users['basic_spec']['birthday'] = $birthday;
        }

        $data[] = $users;
        return $data;
    }
}
