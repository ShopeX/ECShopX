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

declare(strict_types=1);
/**
 * This file is part of Shopex .
 *
 * @link     https://www.shopex.cn
 * @document https://club.shopex.cn
 * @contact  dev@shopex.cn
 */

namespace AdaPayBundle\Services;

use AdaPayBundle\Services\Adapay\AdaPayCommon;
use AdaPayBundle\Services\Adapay\CorpMember;
use AdaPayBundle\Services\Adapay\Drawcash;
use AdaPayBundle\Services\Adapay\FreezeAccount;
use AdaPayBundle\Services\Adapay\Member;
use AdaPayBundle\Services\Adapay\Payment;

//use AdaPayBundle\Services\AdaPayTools;
//use AdaPayBundle\Services\AdaPayRequests;

use AdaPayBundle\Services\Adapay\PaymentConfirm;
use AdaPayBundle\Services\Adapay\PaymentReverse;
use AdaPayBundle\Services\Adapay\Refund;
use AdaPayBundle\Services\Adapay\SettleAccount;
use AdaPayBundle\Services\Adapay\SettleAccountTransfer;
use AdaPayBundle\Services\Adapay\UnFreezeAccount;
use Dingo\Api\Exception\ResourceException;

class AdaPayService
{
    public function MemberUpdate($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new Member();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'member_id' => $params['member_id'],
            'location' => $params['location'] ?? '',
            'email' => $params['email'] ?? '',
            'gender' => $params['gender'] ?? '',
            'nickname' => $params['nickname'] ?? '',
            'tel_no' => $params['tel_no'] ?? '',
        ];
        if (isset($params['disabled'])) {
            $apiParams['disabled'] = $params['disabled'];
        }
        $adaPayObj->update($apiParams);

        # 对进件结果进行处理
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function MemberCreate($apiParams)
    {
        $adaPayConf = [
            'api_key_test' => $apiParams['merchant_info']['test_api_key'],
            'api_key_live' => $apiParams['merchant_info']['live_api_key'],
            'rsa_private_key' => $apiParams['merchant_info']['rsa_private_key'],
        ];

        $adaPayCommon = new AdaPayCommon();
        $adaPayCommon->init($adaPayConf, config('adapay.env'), true);

        $params = [
            'adapay_func_code' => 'members.realname',
            'app_id' => $apiParams['merchant_info']['app_id'] ?? '',
            'member_id' => $apiParams['member_id'],
            'location' => $apiParams['location'] ?? '',
            'email' => $apiParams['email'] ?? '',
            'gender' => $apiParams['gender'] ?? '',
            'nickname' => $apiParams['nickname'] ?? '',
            'tel_no' => $apiParams['tel_no'] ?? '',
            'user_name' => $apiParams['user_name'] ?? '',
            'cert_type' => $apiParams['cert_type'] ?? '00',
            'cert_id' => $apiParams['cert_id'] ?? '',
        ];
        $adaPayCommon->requestAdapay($params, $adaPayConf['rsa_private_key']);

        # 对进件结果进行处理
        if ($adaPayCommon->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayCommon->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayCommon->result, true));
        }

        return $adaPayCommon->result;
    }

    public function CorpMemberCreate($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $corpMember = new CorpMember();
        $corpMember->init($adaPayConf, config('adapay.env'), true);

        //app('log')->info('上传文件 ：' . var_export($_FILES["attach_file"], true));
        $tmpPath = tempnam('/tmp', 'CorpMember') . rand(111111, 999999) . '.png';
        file_put_contents($tmpPath, $params['file_content']);
        $file_real_path = realpath($tmpPath);

        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'member_id' => $params['member_id'],
            'order_no' => $params['order_no'],
            'name' => $params['name'] ?? '',
            'prov_code' => $params['prov_code'] ?? '',
            'area_code' => $params['area_code'] ?? '',
            'social_credit_code' => $params['social_credit_code'] ?? '',
            'social_credit_code_expires' => $params['social_credit_code_expires'] ?? '',
            'business_scope' => $params['business_scope'] ?? '',
            'legal_person' => $params['legal_person'] ?? '',
            'legal_cert_id' => $params['legal_cert_id'] ?? '',
            'legal_cert_id_expires' => $params['legal_cert_id_expires'] ?? '',
            'legal_mp' => $params['legal_mp'] ?? '',
            'address' => $params['address'] ?? '',
            'zip_code' => $params['zip_code'] ?? '',
            'telphone' => $params['telphone'] ?? '',
            'email' => $params['email'] ?? '',
            'attach_file' => fopen($file_real_path, 'r'),
            'bank_code' => $params['bank_code'] ?? '',
            'bank_acct_type' => $params['bank_acct_type'] ?? '',
            'card_no' => $params['card_no'] ?? '',
            'card_name' => $params['card_name'] ?? '',
            'notify_url' => config('adapay.notify_url'),
        ];
        $corpMember->create($apiParams);
        if ($corpMember->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($corpMember->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($corpMember->result, true));
        }

        return $corpMember->result;
    }

    public function CorpMemberUpdate($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new CorpMember();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);
        $apiParams = [
            'adapay_func_code' => 'corp_members.update',
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'member_id' => $params['member_id'],
            'order_no' => $params['order_no'],
            'name' => $params['name'] ?? '',
            'prov_code' => $params['prov_code'] ?? '',
            'area_code' => $params['area_code'] ?? '',
            'social_credit_code' => $params['social_credit_code'] ?? '',
            'social_credit_code_expires' => $params['social_credit_code_expires'] ?? '',
            'business_scope' => $params['business_scope'] ?? '',
            'legal_person' => $params['legal_person'] ?? '',
            'legal_cert_id' => $params['legal_cert_id'] ?? '',
            'legal_cert_id_expires' => $params['legal_cert_id_expires'] ?? '',
            'legal_mp' => $params['legal_mp'] ?? '',
            'address' => $params['address'] ?? '',
            'zip_code' => $params['zip_code'] ?? '',
            'telphone' => $params['telphone'] ?? '',
            'email' => $params['email'] ?? '',
            'notify_url' => config('adapay.notify_url'),
        ];
        
        if (isset($params['file_content']) && $params['file_content']) {
            //app('log')->info('上传文件 ：' . var_export($_FILES["attach_file"], true));
            $tmpPath = tempnam('/tmp', 'CorpMember') . rand(111111, 999999) . '.png';
            file_put_contents($tmpPath, $params['file_content']);
            $file_real_path = realpath($tmpPath);
            $apiParams['attach_file'] = fopen($file_real_path, 'r');
        }
        
        $adaPayObj->update($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function SettleAccountCreate($params)
    {
        // FIXME: check performance
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $settleAccount = new SettleAccount();
        $settleAccount->init($adaPayConf, config('adapay.env'), true);

        $accountInfo = $params['account_info'];
        $bank_acct_type = $accountInfo['bank_acct_type'] ?? '2';
        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'member_id' => $params['member_id'],
            'channel' => $params['channel'] ?? 'bank_account', //目前仅支持：bank_account（银行卡）
            'account_info' => [
                'card_id' => $accountInfo['card_id'] ?? '',
                'card_name' => $accountInfo['card_name'] ?? '',
                'tel_no' => $accountInfo['tel_no'] ?? '',
                //"bank_name" => $accountInfo['bank_name'] ?? "",
                'bank_acct_type' => $bank_acct_type, //1-对公；2-对私
            ],
        ];

        //2-对私
        if ($bank_acct_type == '2') {
            $apiParams['account_info']['cert_id'] = $accountInfo['cert_id'] ?? '';
            $apiParams['account_info']['cert_type'] = $accountInfo['cert_type'] ?? '';
        }

        //1-对公
        if ($bank_acct_type == '1') {
            $apiParams['account_info']['bank_code'] = $accountInfo['bank_code'] ?? '';
            $apiParams['account_info']['prov_code'] = $accountInfo['prov_code'] ?? '';
            $apiParams['account_info']['area_code'] = $accountInfo['area_code'] ?? '';
        }

        $settleAccount->create($apiParams);
        if ($settleAccount->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($settleAccount->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($settleAccount->result, true));
        }

        return $settleAccount->result;
    }

    public function SettleAccountDelete($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $settleAccount = new SettleAccount();
        $settleAccount->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'member_id' => $params['member_id'],
            'settle_account_id' => $params['settle_account_id'],
        ];
        $settleAccount->delete($apiParams);
        if ($settleAccount->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($settleAccount->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($settleAccount->result, true));
        }

        return $settleAccount->result;
    }

    public function SettleAccountFreeze($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new FreezeAccount();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'order_no' => $params['order_no'],
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'member_id' => $params['member_id'],
            'trans_amt' => $params['trans_amt'] ?? '0.00',
        ];
        $adaPayObj->create($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function SettleAccountUnfreeze($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new UnFreezeAccount();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'order_no' => $params['order_no'],
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'account_freeze_id' => $params['account_freeze_id'],
        ];
        $adaPayObj->create($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function SettleAccountTransfer($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new SettleAccountTransfer();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'order_no' => $params['order_no'],
            'trans_amt' => (string)$params['trans_amt'],
            'out_member_id' => (string)$params['out_member_id'],
            'in_member_id' => (string)$params['in_member_id'],
        ];
        $adaPayObj->create($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function PaymentCreate($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new Payment();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'order_no' => $params['order_no'],
            'pay_channel' => $params['pay_channel'],
            'pay_amt' => $params['pay_amt'],
            'pay_mode' => $params['pay_mode'] ?? '',
            'goods_title' => $params['goods_title'] ?? '',
            'goods_desc' => $params['goods_desc'] ?? '',
            'currency' => $params['currency'] ?? 'cny',
            'div_members' => $params['div_members'] ?? '',
            'description' => $params['description'] ?? '',
            'time_expire' => $params['time_expire'] ?? '',
            'expend' => $params['expend'] ?? '',
            'fee_mode' => $params['fee_mode'] ?? '',
            'notify_url' => config('adapay.notify_url'),
        ];
        $adaPayObj->create($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function PaymentQuery($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new Payment();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'payment_id' => $params['payment_id'] ?? '',
        ];
        $adaPayObj->query($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function PaymentConfirmCreate($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new PaymentConfirm();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'payment_id' => $params['payment_id'],
            'order_no' => $params['order_no'],
            'confirm_amt' => $params['confirm_amt'],
            'description' => $params['description'] ?? '',
            'div_members' => $params['div_members'] ?? [],
            'fee_mode' => $params['fee_mode'] ?? '',
        ];
        $adaPayObj->create($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function RefundCreate($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new Refund();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'payment_id' => $params['payment_id'],
            'refund_order_no' => $params['refund_order_no'] ?? '',
            'refund_amt' => $params['refund_amt'] ?? '',
            'reason' => $params['reason'] ?? '',
            'expend' => $params['expend'] ?? '',
            'div_members' => $params['div_members'] ?? '',
            'fail_fast' => $params['fail_fast'] ?? '',
            'notify_url' => config('adapay.notify_url'),
        ];
        $adaPayObj->create($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($params, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    public function PaymentReverseCreate($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayObj = new PaymentReverse();
        $adaPayObj->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'payment_id' => $params['payment_id'] ?? '',
            'order_no' => $params['order_no'] ?? '',
            'reverse_amt' => $params['reverse_amt'] ?? '',
            'reason' => $params['reason'] ?? '',
            'expand' => $params['expand'] ?? '',
            'notify_url' => config('adapay.notify_url'),
        ];
        $adaPayObj->create($apiParams);
        if ($adaPayObj->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($params, true));
            app('log')->error('错误结果' . var_export($adaPayObj->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayObj->result, true));
        }

        return $adaPayObj->result;
    }

    //余额查询
    public function SettleAccountBalance($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $settleAccount = new SettleAccount();
        $settleAccount->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'settle_account_id' => $params['settle_account_id'] ?? '',
            'member_id' => $params['member_id'] ?? '0',
            'acct_type' => $params['acct_type'] ?? '01', //账户类型，01或者为空是基本户，02是手续费账户，03是过渡户
        ];

        //如果查询子商户余额，settle_account_id 必须
        if ($apiParams['member_id'] != '0') {
            if (!$apiParams['settle_account_id']) {
                throw new ResourceException('settle_account_id error.');
            }
        }

        if (!$apiParams['settle_account_id']) {
            unset($apiParams['settle_account_id']);
        }
        
        $settleAccount->balance($apiParams);

        if ($settleAccount->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($settleAccount->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($settleAccount->result, true));
        }

        return $settleAccount->result;
    }

    //提现
    public function DrawCashCreate($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $drawCash = new Drawcash();
        $drawCash->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'order_no' => $params['order_no'],
            'cash_type' => $params['cash_type'],
            'cash_amt' => $params['cash_amt'],
            'member_id' => $params['member_id'],
            'remark' => $params['remark'] ?? '',
            'fee_mode' => $params['fee_mode'] ?? '',
            'notify_url' => config('adapay.notify_url'),
        ];
        $drawCash->create($apiParams);

        # 对进件结果进行处理
        if ($drawCash->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($drawCash->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($drawCash->result, true));
        }

        return $drawCash->result;
    }

    //余额支付
    public function SettleAccountBalancePay($apiParams)
    {
        $adaPayConf = [
            'api_key_test' => $apiParams['merchant_info']['test_api_key'],
            'api_key_live' => $apiParams['merchant_info']['live_api_key'],
            'rsa_private_key' => $apiParams['merchant_info']['rsa_private_key'],
        ];

        $adaPayCommon = new AdaPayCommon();
        $adaPayCommon->init($adaPayConf, config('adapay.env'), true);

        $params = [
            'adapay_func_code' => 'settle_accounts.balancePay',
            'app_id' => $apiParams['merchant_info']['app_id'] ?? '',
            'order_no' => $apiParams['order_no'] ?? '',
            'out_member_id' => $apiParams['out_member_id'],
            'in_member_id' => $apiParams['in_member_id'],
            'trans_amt' => $apiParams['trans_amt'] ?? '0',
            'goods_title' => $apiParams['goods_title'] ?? $apiParams['trans_type'],
            'goods_desc' => $apiParams['goods_desc'] ?? $apiParams['trans_type'],
        ];
        $adaPayCommon->requestAdapay($params, $adaPayConf['rsa_private_key']);

        # 对进件结果进行处理
        if ($adaPayCommon->isError()) {
            //失败处理
            app('log')->info('请求参数：' . var_export($apiParams, true));
            app('log')->error('错误结果' . var_export($adaPayCommon->result, true));
        } else {
            //成功处理
            app('log')->info('处理成功：' . var_export($adaPayCommon->result, true));
        }

        return $adaPayCommon->result;
    }

    public function Jumppay($params)
    {
        $adaPayConf = [
            'api_key_test' => $params['merchant_info']['test_api_key'],
            'api_key_live' => $params['merchant_info']['live_api_key'],
            'rsa_private_key' => $params['merchant_info']['rsa_private_key'],
        ];

        $adaPayCommon = new AdaPayCommon();
        $adaPayCommon->init($adaPayConf, config('adapay.env'), true);

        $apiParams = [
            'adapay_func_code' => $params['adapay_func_code'],
            'app_id' => $params['merchant_info']['app_id'] ?? '',
            'order_no' => $params['order_no'],
            'pay_channel' => $params['pay_channel'],
            'pay_amt' => $params['pay_amt'],
            'pay_mode' => $params['pay_mode'] ?? '',
            'goods_title' => $params['goods_title'] ?? '',
            'goods_desc' => $params['goods_desc'] ?? '',
            'currency' => $params['currency'] ?? 'cny',
            'div_members' => $params['div_members'] ?? '',
            'description' => $params['description'] ?? '',
            'time_expire' => $params['time_expire'] ?? '',
            'expend' => $params['expend'] ?? '',
            'fee_mode' => $params['fee_mode'] ?? '',
            'notify_url' => config('adapay.notify_url'),
        ];
        $adaPayCommon->requestAdapayUits($apiParams, $adaPayConf['rsa_private_key']);
        if ($adaPayCommon->isError()) {
            //失败处理
            logger('adapay')->info('请求参数：' . var_export($apiParams, true));
            logger('adapay')->error('错误结果' . var_export($adaPayCommon->result, true));
        } else {
            //成功处理
            logger('adapay')->info('处理成功：' . var_export($adaPayCommon->result, true));
        }

        return $adaPayCommon->result;
    }
}
