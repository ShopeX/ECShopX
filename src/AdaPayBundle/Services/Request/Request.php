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

namespace AdaPayBundle\Services\Request;

use GuzzleHttp\Client as Client;
use Dingo\Api\Exception\ResourceException;
use AdaPayBundle\Services\AdaPayService;
use PaymentBundle\Services\Payments\AdaPaymentService;

class Request
{
    private $adaPayService;

    public function __construct()
    {
        $this->adaPayService = new AdaPayService();
    }

    public function call($params)
    {
        $adaPaymentService = new AdaPaymentService();
        $params['merchant_info'] = $adaPaymentService->getPaymentSetting($params['company_id']);

        switch ($params['api_method']) {
            case 'MerchantUser.create':
                throw new ResourceException('暂不支持开户流程');
                // $result = $this->adaPayService->MerchantUserCreate($params);
                break;
            case 'MerchantProfile.merProfileForAudit':
                throw new ResourceException('暂不支持开户流程');
                // $result = $this->adaPayService->MerchantProfileForAudit($params);
                break;
            case 'MerchantConf.create'://商户入驻
                throw new ResourceException('暂不支持开户流程');
                // $result = $this->adaPayService->MerchantConfCreate($params);
                break;
            case 'MerchantProfile.merProfilePicture':
                throw new ResourceException('暂不支持开户流程');
                // $result = $this->adaPayService->MerchantProfileUploadPic($params);
                break;
            case 'MerchantProfile.merProfileAuditStatus':
                throw new ResourceException('暂不支持开户流程');
                // $result = $this->adaPayService->MerProfileAuditStatus($params);
                break;
            case 'Member.create'://创建实名用户对象
                $result = $this->adaPayService->MemberCreate($params);
                break;
            case 'Member.update':
                $result = $this->adaPayService->MemberUpdate($params);
                break;
            case 'CorpMember.create'://同步创建企业用户和结算账号
                $result = $this->adaPayService->CorpMemberCreate($params);
                break;
            case 'CorpMember.update':
                $result = $this->adaPayService->CorpMemberUpdate($params);
                break;
            case 'SettleAccount.create':
                $result = $this->adaPayService->SettleAccountCreate($params);
                break;
            case 'SettleAccount.delete'://删除结算账户对象
                $result = $this->adaPayService->SettleAccountDelete($params);
                break;
            case 'Payment.create':
                if (isset($params['adapay_func_code']) && $params['adapay_func_code']) {
                    $result = $this->adaPayService->Jumppay($params);
                } else {
                    $result = $this->adaPayService->PaymentCreate($params);
                }
                break;
            case 'Payment.query':
                $result = $this->adaPayService->PaymentQuery($params);
                break;
            case 'PaymentConfirm.create': //支付确认对象
                $result = $this->adaPayService->PaymentConfirmCreate($params);
                break;
            case 'PaymentReverse.create':// 支付撤销
                $result = $this->adaPayService->PaymentReverseCreate($params);
                break;
            case 'Refund.create':// 退款
                $result = $this->adaPayService->RefundCreate($params);
                break;
            case 'DrawCash.create':// 提现
                $result = $this->adaPayService->DrawCashCreate($params);
                break;
            case 'SettleAccount.balance':
                $result = $this->adaPayService->SettleAccountBalance($params);
                break;
            case 'SettleAccount.transfer':
                $result = $this->adaPayService->SettleAccountBalancePay($params);
                break;
        }
        return ['data' => $result];
    }
}
