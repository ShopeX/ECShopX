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

namespace AdaPayBundle\Services\CallBack;

use AdaPayBundle\Services\CorpMemberService;
use AdaPayBundle\Services\MemberService as AdaPayMemberService;
use AdaPayBundle\Services\OpenAccountService;
use AdaPayBundle\Services\SettleAccountService;
use CompanysBundle\Services\OperatorsService;
use PromotionsBundle\Services\SmsManagerService;

class CorpMember
{
    /**
     * 开户成功
     *
     * "member_id": "2019072601295",
     * "created_time": "1564736349",
     * "object":"corp_member",
     * "order_no": "123456789",
     * "prod_mode": "true",
     * "app_id": "sfjeijibbTe5jLGCi5rzfH4OqPW9KCif913",
     * "audit_state": "D",
     * "audit_desc": "开户成功",
     * "settle_account_id": "0006440476699456"
     * @param array $data
     * @return array
     */
    public function succeeded($data = [])
    {
        //更新用户表审核状态
        $updateStateWhere = [
            'id' => $data['member_id'],
            'app_id' => $data['app_id'],
        ];
        $updateData = [
            'audit_state' => $data['audit_state'],
            'audit_desc' => $data['audit_desc'] ?? '',
        ];
        if (in_array($data['audit_state'], ['D', 'E'])) {
            $updateData['is_created'] = 1;
        }

        $adaPayMemberService = new AdaPayMemberService();
        $rs = $adaPayMemberService->updateOneBy($updateStateWhere, $updateData);

        //更新企业用户表审核状态
        $filter = [
            'member_id' => $data['member_id'],
            'app_id' => $data['app_id'],
        ];
        $updateData = [
            'audit_state' => $data['audit_state'],
            'audit_desc' => $data['audit_desc'] ?? '',
        ];
        $obj = new CorpMemberService();
        $res = $obj->updateOneBy($filter, $updateData);

        // 是否需要创建 企业版结算账号
        if ($data['audit_state'] == 'E') {
            $filter = [
                'member_id' => $data['member_id'],
                'app_id' => $data['app_id'],
            ];
            $updateData = [
                'settle_account_id' => $data['settle_account_id'],
            ];
            $obj = new SettleAccountService();
            $obj->updateOneBy($filter, $updateData);

            if ($rs['operator_type'] == 'dealer') {
                $operatorsService = new OperatorsService();
                $operatorsService->updateOneBy(['company_id' => $rs['company_id'], 'operator_id' => $rs['operator_id']], ['adapay_open_account_time' => time()]);
            }
        }

        if ($rs['is_sms']) {
            try {
                $data = ['mer_name' => $res['name']];
                $smsManagerService = new SmsManagerService($rs['company_id']);
                $smsManagerService->send($rs['tel_no'], $rs['company_id'], 'sub_account_approved', $data);
            } catch (\Exception $e) {}
        }


        return ['success'];
    }

    /**
     * 开户失败
     *
     * "member_id": "2019072601295",
     * "created_time": "1564736349",
     * "object":"corp_member",
     * "order_no": "123456789",
     * "prod_mode": "true",
     * "app_id": "sfjeijibbTe5jLGCi5rzfH4OqPW9KCif913",
     * "audit_state": "C",
     * "audit_desc": "开户失败"
     * @param array $data
     * @return array
     */
    public function failed($data = [])
    {
        return ['success'];
    }
}
