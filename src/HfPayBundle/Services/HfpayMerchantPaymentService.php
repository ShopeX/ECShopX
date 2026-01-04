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

namespace HfPayBundle\Services;

use HfPayBundle\Entities\HfpayBankCard;
use HfPayBundle\Entities\HfpayCashRecord;
use HfPayBundle\Entities\HfpayMerchantPayment;
use HfPayBundle\Events\HfPayPopularizeWithdrawEvent;
use HfPayBundle\Services\HfpayService as hfpayClientService;
use OrdersBundle\Services\MerchantTradeService;
use PaymentBundle\Services\Payments\HfPayService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class HfpayMerchantPaymentService
{
    public $entityRepository;
    public $cashRecordRepository;
    public $bankRepository;

    public function __construct()
    {
        // 53686f704578
        $this->entityRepository = app('registry')->getManager('default')->getRepository(HfpayMerchantPayment::class);
        $this->cashRecordRepository = app('registry')->getManager('default')->getRepository(HfpayCashRecord::class);
        $this->bankRepository = app('registry')->getManager('default')->getRepository(HfpayBankCard::class);
    }

    /**
     * 平台账户转账   BadRequestHttpException
     */
    public function merchantPayment($params)
    {
        $company_id = $params['company_id'];
        $rel_scene_id = $params['rel_scene_id'];
        $rel_scene_name = $params['rel_scene_name'];
        $user_id = $params['user_id'];
        $trans_amt = $params['trans_amt']; //分
        $spbill_create_ip = $params['spbill_create_ip'];

        //判断汇付收款是否设置
        $hfpay_service = new HfPayService();
        $paymentSetting = $hfpay_service->getPaymentSetting($company_id);
        if (!$paymentSetting) {
            throw new BadRequestHttpException('汇付天下支付配置缺失');
        }

        if ($paymentSetting['is_open'] == 'false') {
            throw new BadRequestHttpException('请检查汇付天下支付是否启用');
        }

        //判断用户是否申请汇付账户
        $user_filter = [
            'user_id' => $user_id
        ];
        $service = new HfpayEnterapplyService();
        $user_hfpay_info = $service->getEnterapply($user_filter);
        if (empty($user_hfpay_info)) {
            throw new BadRequestHttpException('请先完成实名认证');
        }

        //判断是否设置取现卡
        $filter = [
            'user_id' => $user_id,
            'is_cash' => 1
        ];
        $brank = $this->bankRepository->getInfo($filter);
        if (empty($brank)) {
            throw new BadRequestHttpException('请绑定提现银行卡');
        }

        //判断是否已转账
        $filter = [
            'company_id' => $company_id,
            'rel_scene_id' => $rel_scene_id,
            'rel_scene_name' => $rel_scene_name
        ];
        $merchant_payment_info = $this->entityRepository->getInfo($filter);
        if (empty($merchant_payment_info)) {
            $_params = [
                'company_id' => $company_id,
                'user_id' => $user_id,
                'rel_scene_id' => $rel_scene_id,
                'rel_scene_name' => $rel_scene_name,
                'mer_cust_id' => $paymentSetting['mer_cust_id'],
                'user_cust_id' => $user_hfpay_info['user_cust_id'],
                'acct_id' => $user_hfpay_info['acct_id'],
                'trans_amt' => $trans_amt,
            ];

            //数据入库
            $this->entityRepository->create($_params);
        }

        //调用汇付转账接口
        if (empty($merchant_payment_info) || (!empty($merchant_payment_info) && $merchant_payment_info['status'] != 1)) {
            $trans_amt_yuan = bcdiv($params['trans_amt'], 100, 2); //转化成元
            $params = [
                'in_cust_id' => $user_hfpay_info['user_cust_id'],
                'in_acct_id' => $user_hfpay_info['acct_id'],
                'trans_amt' => $trans_amt_yuan
            ];
            $service = new hfpayClientService($company_id);
            $reslut = $service->pay026($params);
            if ($reslut['resp_code'] == 'C00000') {
                //修改转账记录
                $update_filter = [
                    'company_id' => $company_id,
                    'rel_scene_id' => $rel_scene_id
                ];
                $update_data = [
                    'status' => 1,
                    'hf_order_id' => $reslut['order_id'],
                    'hf_order_date' => $reslut['order_date'],
                    'resp_code' => $reslut['resp_code'],
                    'resp_desc' => $reslut['resp_desc']
                ];
                $this->entityRepository->updateOneBy($update_filter, $update_data);
            } else {
                $data['status'] = 'FAIL';
                $data['error_desc'] = $reslut['resp_desc'];

                return $data;
            }
        }

        //触发用户提现
        //生成提现记录
        $paymentData = [
            'company_id' => $company_id,
            'rel_scene_id' => $rel_scene_id,
            'rel_scene_name' => 'popularize_rebate_cash_withdrawal',
            're_user_name' => '',
            'amount' => $trans_amt, //提现金额 （分）
            'user_id' => $user_id,
            'open_id' => '',
            'payment_desc' => '佣金提现',
            'spbill_create_ip' => $spbill_create_ip,
            'payment_action' => 'HFPAY',
            'check_name' => 'NO_CHECK',
            'hf_cash_type' => 'T1',
            'user_cust_id' => $user_hfpay_info['user_cust_id'],
            'bind_card_id' => $brank['bind_card_id']
        ];
        $merchantTradeService = new MerchantTradeService();
        $merchant_trade_info = $merchantTradeService->create($paymentData);

        //提现处理事件
        $eventData = [
            'merchant_trade_id' => $merchant_trade_info['merchant_trade_id']
        ];
        event(new HfPayPopularizeWithdrawEvent($eventData));

        $data['status'] = 'SUCCESS';
        $data['error_desc'] = 'SUCCESS';

        return $data;
    }
}
