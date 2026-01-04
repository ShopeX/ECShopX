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

namespace HfPayBundle\Http\ThirdApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use DepositBundle\Services\DepositTrade;
use HfPayBundle\Entities\HfpayBankCard;
use HfPayBundle\Entities\HfpayCashRecord;
use HfPayBundle\Entities\HfpayEnterapply;
use HfPayBundle\Events\HfPayDistributorWithdrawSuccessEvent;
use HfPayBundle\Services\src\Kernel\HfSign;
use Illuminate\Http\Request;
use OrdersBundle\Entities\MerchantPaymentTrade;
use OrdersBundle\Services\TradeService;

class HfPay extends Controller
{
    /**
     * @SWG\Post(
     *     path="/hfpay/notify",
     *     summary="接收汇付异步推送消息",
     *     tags={"hfpay"},
     *     description="接收汇付异步推送消息",
     *     operationId="notify",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="RECV_ORD_ID_", type="string", description="响应结果"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfpayErrorRespones") ) )
     * )
     */
    public function notify(Request $request)
    {
        $check_value = $request->input('check_value');
        //解密汇付数据
        $sign = new HfSign();
        $sign->strLogCofigFilePath = storage_path('hfpay/cfcalog.conf');
        $sign->getCFCAInitialize();
        //验证接口返回的签名数据
        $source_data = $sign->getCFCASignSourceData($check_value);
        $sign_source_data = '';
        if (!empty($source_data['strMsgP7AttachedSource'])) {
            $sign_source_data = json_decode($source_data['strMsgP7AttachedSource'], true);
        }
        app('log')->debug('汇付回调data--->>>' . var_export($sign_source_data, 1));
        //应答返回码
        $resp_code = $sign_source_data['resp_code'];

        $order_id = $sign_source_data['order_id'];
        $mer_priv = $sign_source_data['mer_priv'];
        if (!empty($mer_priv)) {
            //支付异步推送
            if ($mer_priv == 'pay') {
                if ($resp_code != 'C00000') {
                    return 'fail';
                }
                $this->pay($sign_source_data);
            }
            //储值异步推送消息
            if ($mer_priv == 'recharge') {
                if ($resp_code != 'C00000') {
                    return 'fail';
                }
                $this->depositRecharge($sign_source_data);
            }
            //企业开户结果
            if ($mer_priv == 'corp01') {
                $this->corp01($sign_source_data);
            }
            //个体户开户结果
            if ($mer_priv == 'solo01') {
                $this->solo01($sign_source_data);
            }
            //店铺提现结果
            if ($mer_priv == 'cash01_distributor') {
                $this->cash01Distributor($sign_source_data);
            }
            //推广员提现结果
            if ($mer_priv == 'cash01_popularize') {
                $this->cash01Popularize($sign_source_data);
            }
        }

        return "RECV_ORD_ID_{$order_id}";
    }

    /**
     * @return string
     * 支付推送
     */
    private function pay($input)
    {
        $order_id = $input['order_id'];
        $out_trans_id = $input['out_trans_id'];

        $tradeService = new TradeService();
        $options['pay_type'] = 'hfpay';
        $options['transaction_id'] = $out_trans_id;
        $status = 'SUCCESS';
        $tradeService->updateStatus($order_id, $status, $options);
        return true;
    }

    /**
     * @return string
     * 储值充值
     */
    private function depositRecharge($input)
    {
        $order_id = $input['order_id'];
        $out_trans_id = $input['out_trans_id'];

        $depositTrade = new DepositTrade();
        $options['pay_type'] = 'hfpay';
        $options['transaction_id'] = $out_trans_id;
        $status = 'SUCCESS';
        $depositTrade->rechargeCallback($order_id, $status, $options);
        return true;
    }

    /**
     * 企业开户异步通知
     */
    private function corp01($input)
    {
        $order_id = $input['order_id'];
        $resp_code = $input['resp_code'];
        $resp_desc = $input['resp_desc'];
        $user_cust_id = $input['user_cust_id'] ?? '';
        $acct_id = $input['acct_id'] ?? '';
        $bind_card_id = $input['bind_card_id'] ?? '';

        $entity_repository = app('registry')->getManager('default')->getRepository(HfpayEnterapply::class);
        $filter = [
            'hf_order_id' => $order_id
        ];
        //查询入驻信息
        $enter_apply_info = $entity_repository->getInfo($filter);
        if (empty($enter_apply_info)) {
            return true;
        }
        //审核成功
        if ($resp_code == 'C00000') {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                //修改审核状态
                $data = [
                    'status' => 3,
                    'user_cust_id' => $user_cust_id,
                    'acct_id' => $acct_id,
                    'resp_code' => $resp_code,
                    'resp_desc' => $resp_desc
                ];
                $entity_repository->updateOneBy($filter, $data);

                //创建取现卡
                if (!empty($bind_card_id)) {
                    //判断是否已绑定过银行卡
                    $hfpay_bnank_repository = app('registry')->getManager('default')->getRepository(HfpayBankCard::class);
                    $_filter = [
                        'distributor_id' => $enter_apply_info['distributor_id'],
                        'bind_card_id' => $bind_card_id
                    ];
                    $bank_info = $hfpay_bnank_repository->getInfo($_filter);
                    //新增取现卡
                    if (empty($bank_info)) {
                        $params = [
                            'company_id' => $enter_apply_info['company_id'],
                            'distributor_id' => $enter_apply_info['distributor_id'],
                            'user_id' => 0,
                            'user_cust_id' => $enter_apply_info['user_cust_id'],
                            'card_type' => 0,
                            'bank_id' => $enter_apply_info['bank_id'],
                            'bank_name' => '',
                            'card_num' => $enter_apply_info['bank_acct_num'],
                            'bind_card_id' => $bind_card_id,
                            'is_cash' => 1
                        ];
                        $hfpay_bnank_repository->create($params);
                    }
                }

                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                throw $e;
            } catch (\Throwable $e) {
                $conn->rollback();
                throw $e;
            }
        } elseif ($resp_code != 'C00001') {
            $data = [
                'status' => 4,
                'resp_code' => $resp_code,
                'resp_desc' => $resp_desc
            ];
            $entity_repository->updateOneBy($filter, $data);
        }
    }

    /**
     * 个体户开户异步通知
     */
    private function solo01($input)
    {
        // ShopEx EcShopX Core Module
        $order_id = $input['order_id'];
        $resp_code = $input['resp_code'];
        $resp_desc = $input['resp_desc'];
        $user_cust_id = $input['user_cust_id'] ?? '';
        $acct_id = $input['acct_id'] ?? '';

        $entity_repository = app('registry')->getManager('default')->getRepository(HfpayEnterapply::class);
        $filter = [
            'hf_order_id' => $order_id
        ];
        //查询入驻信息
        $enter_apply_info = $entity_repository->getInfo($filter);
        if (empty($enter_apply_info)) {
            return true;
        }
        //审核成功
        if ($resp_code == 'C00000') {
            //修改审核状态
            $data = [
                'status' => 3,
                'user_cust_id' => $user_cust_id,
                'acct_id' => $acct_id
            ];
            $entity_repository->updateOneBy($filter, $data);
        } elseif ($resp_code != 'C00001') {
            $data = [
                'status' => 4,
                'resp_code' => $resp_code,
                'resp_desc' => $resp_desc
            ];
            $entity_repository->updateOneBy($filter, $data);
        }
    }

    /**
     *  店铺取现通知
     */
    private function cash01Distributor($input)
    {
        $resp_code = $input['resp_code'];
        $resp_desc = $input['resp_desc'];
        $order_id = $input['order_id'];
        $real_trans_amt = bcmul($input['real_trans_amt'], 100);

        $cash_record_repository = app('registry')->getManager('default')->getRepository(HfpayCashRecord::class);
        $filter = [
            'hf_order_id' => $order_id
        ];
        $cash_record = $cash_record_repository->getInfo($filter);
        if (empty($cash_record)) {
            return true;
        }

        //取现成功
        if ($resp_code == 'C00000') {
            $data = [
                'cash_status' => 2,
                'real_trans_amt' => $real_trans_amt,
                'resp_code' => $resp_code,
                'resp_desc' => $resp_desc
            ];

            $cash_record_repository->updateOneBy($filter, $data);

            event(new HfPayDistributorWithdrawSuccessEvent($cash_record));
        } elseif ($resp_code == 'C00001') {
            $data = [
                'cash_status' => 3,
                'resp_code' => $resp_code,
                'resp_desc' => $resp_desc
            ];

            $cash_record_repository->updateOneBy($filter, $data);
        }
    }

    /**
     *  推广员取现通知
     */
    private function cash01Popularize($input)
    {
        $resp_code = $input['resp_code'];
        $resp_desc = $input['resp_desc'];
        $order_id = $input['order_id'];
        $real_trans_amt = bcmul($input['real_trans_amt'], 100);

        $merchant_payment_trade_repository = app('registry')->getManager('default')->getRepository(MerchantPaymentTrade::class);
        $filter = [
            'hf_order_id' => $order_id
        ];
        $cash_record = $merchant_payment_trade_repository->getInfo($filter);
        if (empty($cash_record)) {
            return true;
        }

        //取现成功
        if ($resp_code == 'C00000') {
            $data = [
                'status' => 'SUCCESS',
                'error_code' => $resp_code,
                'error_desc' => $resp_desc
            ];
            $merchant_payment_trade_repository->updateOneBy($filter, $data);
        } elseif ($resp_code == 'C00001') {
            $data = [
                'status' => 'FAIL',
                'error_code' => $resp_code,
                'error_desc' => $resp_desc
            ];
            $merchant_payment_trade_repository->updateOneBy($filter, $data);
        }
    }
}
