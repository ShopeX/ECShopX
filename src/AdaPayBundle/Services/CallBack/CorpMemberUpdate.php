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

use AdaPayBundle\Services\BankCodeService;
use AdaPayBundle\Services\CorpMemberService;
use AdaPayBundle\Services\MemberService as AdaPayMemberService;
use AdaPayBundle\Services\OpenAccountService;
use AdaPayBundle\Services\SettleAccountService;
use AdaPayBundle\Services\SubMerchantService;
use PromotionsBundle\Services\SmsManagerService;

class CorpMemberUpdate
{
    public const AUDIT_UPDATE_SUCCESS = 'S'; // 企业账户更新成功
    public const AUDIT_UPDATE_FAIL = 'F'; // 企业账户更新失败

    /**
     * 企业用户修改成功
     *
     * "app_id":"app_XXXXXXXX",
     * "created_time":"1606440833",
     * "id":"002112020112709335210178209923564879872",
     * "object":"corp_member_update",
     * "order_no":"123456789",
     * "member_id":"adapay_001",
     * "prod_mode":"true",
     * "audit_state":"S",
     * "audit_desc":"更新企业用户对象成功"
     * @param array $data
     * @return array
     */
    public function succeeded($data = [])
    {
        $adaPayMemberService = new AdaPayMemberService();
        $corpMemberService = new CorpMemberService();

        if ($data['audit_state'] == self::AUDIT_UPDATE_SUCCESS) {
            $data['audit_state'] = SubMerchantService::AUDIT_ACCOUNT_FAIL;
        } elseif ($data['audit_state'] == self::AUDIT_UPDATE_FAIL) {
            $data['audit_state'] = SubMerchantService::AUDIT_FAIL;
        }

        //更新用户表审核状态
        $updateStateWhere = [
            'member_id' => $data['member_id'],
            'app_id' => $data['app_id'],
        ];
        $updateData = [
            'audit_state' => $data['audit_state'],
            'audit_desc' => $data['audit_desc'] ?? '',
        ];
        $rs = $adaPayMemberService->adapayMemberUpdateLogRepository->getInfo($updateStateWhere, ['id' => 'DESC']);
        $logData = json_decode($rs['data'], true);
        $adaPayMemberService->adapayMemberUpdateLogRepository->updateOneBy(['id' => $rs['id']], $updateData);

        if ($data['audit_state'] == SubMerchantService::AUDIT_ACCOUNT_FAIL) {
            $res = $corpMemberService->syncBaseData($rs);

            $settleAccountService = new SettleAccountService();
            $subMerchantService = new SubMerchantService();
            $settleAccount = $settleAccountService->getInfo(['app_id' => $data['app_id'], 'member_id' => $data['member_id']]);

            if ($settleAccount['settle_account_id']) {
                $deleteSettleResult = $subMerchantService->deleteSettleAccount($settleAccount['company_id'], $data['app_id'], $settleAccount['settle_account_id'], ['id' => $data['member_id']]);
                if ($deleteSettleResult['data']['status'] == 'failed' && isset($deleteSettleResult['data']['error_code']) && $deleteSettleResult['data']['error_code'] != 'account_not_exists') {
                    $adaPayMemberService->adapayMemberUpdateLogRepository->updateOneBy(['id' => $rs['id']], ['audit_desc' => $deleteSettleResult['data']['error_msg']]);

                    $updateData['audit_desc'] = $deleteSettleResult['data']['error_msg'];
                    $adaPayMemberService->updateBy(['id' => $data['member_id'], 'app_id' => $data['app_id']], $updateData);
                    $corpMemberService->updateBy(['member_id' => $data['member_id'], 'app_id' => $data['app_id']], $updateData);

                    if ($res['member_info']['is_sms']) {
                        $this->sendSms($res['corp_info'], $res['member_info']);
                    }
                    return ['success'];
                }
            }

            $bankCodeService = new BankCodeService();
            $settleData = [
                'bank_acct_type' => $logData['bank_acct_type'] ?? '2',//银行账户类型：1-对公；2-对私
                'card_id' => $logData['card_no'] ?? '',
                'card_name' => $logData['card_name'] ?? '',
                'cert_id' => $logData['legal_cert_id'] ?? '',
                'cert_type' => $logData['cert_type'] ?? '00',
                'tel_no' => $logData['legal_mp'] ?? '',
//                'channel' => $logData['channel'] ?? 'bank_account',
                'bank_code' => $logData['bank_code'],
                'bank_name' => $bankCodeService->getBankName($logData['bank_code']),
                'prov_code' => $logData['prov_code'],
                'area_code' => $logData['area_code'],
            ];
            $settleAccountService->updateOneBy(['app_id' => $data['app_id'], 'member_id' => $data['member_id']], $settleData);
            $createSettleResult = $subMerchantService->createSettleAccount($res['member_info']['company_id'], $data['app_id'], $res['member_info']['id']);
            if ($createSettleResult['data']['status'] == 'succeeded') {
                // 更新状态
                $adaPayMemberService->adapayMemberUpdateLogRepository->updateOneBy(['id' => $rs['id']], ['audit_state' => SubMerchantService::AUDIT_SUCCESS]);

                $updateData['audit_state'] = SubMerchantService::AUDIT_SUCCESS;
                $adaPayMemberService->updateBy(['id' => $data['member_id'], 'app_id' => $data['app_id']], $updateData);
                $corpMemberService->updateBy(['member_id' => $data['member_id'], 'app_id' => $data['app_id']], $updateData);

                // 更新结算ID
                $settleAccountService->updateOneBy(['app_id' => $data['app_id'], 'member_id' => $data['member_id']], ['settle_account_id' => $createSettleResult['data']['id']]);
            } else {
                // 账户创建失败
                $adaPayMemberService->adapayMemberUpdateLogRepository->updateOneBy(
                    ['id' => $rs['id']],
                    ['audit_state' => SubMerchantService::AUDIT_ACCOUNT_FAIL,
                        'audit_desc' => '###' . $createSettleResult['data']['error_msg'] . '(请尽快提交修改信息，避免影响分账功能)']
                );

                $updateData['audit_desc'] = $createSettleResult['data']['error_msg'];
                $adaPayMemberService->updateBy(['id' => $data['member_id'], 'app_id' => $data['app_id']], $updateData);
                $corpMemberService->updateBy(['member_id' => $data['member_id'], 'app_id' => $data['app_id']], $updateData);
            }
        }

        $memberInfo = $adaPayMemberService->getInfo(['id' => $data['member_id']]);
        $corpInfo = $corpMemberService->getInfo(['member_id' => $data['member_id']]);

        if ($memberInfo['is_sms']) {
            $this->sendSms($corpInfo, $memberInfo);
        }

        return ['success'];
    }

    private function sendSms($corpMember, $adaPayMember): bool
    {
        try {
            $data = ['mer_name' => $corpMember['name']];
            $smsManagerService = new SmsManagerService($adaPayMember['company_id']);
            $smsManagerService->send($adaPayMember['tel_no'], $adaPayMember['company_id'], 'sub_account_approved', $data);
        } catch (\Exception $e) {}

        return true;
    }


    /**
     * 企业用户修改失败
     *
     * "app_id":"app_XXXXXXXX",
     * "created_time":"1606440833",
     * "id":"002112020112709335210178209923564879872",
     * "object":"corp_member_update",
     * "order_no":"123456789",
     * "member_id":"adapay_001",
     * "prod_mode":"true",
     * "audit_state":"F",
     * "audit_desc":"更新企业用户对象失败"
     *
     * @param array $data
     * @return array
     */
    public function failed($data = [])
    {
        return ['success'];
    }
}
