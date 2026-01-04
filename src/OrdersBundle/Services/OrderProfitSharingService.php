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

namespace OrdersBundle\Services;

use AftersalesBundle\Entities\AftersalesRefund;
use DistributionBundle\Entities\Distributor;
use HfPayBundle\Events\HfpayProfitSharingEvent;
use HfPayBundle\Services\HfpayEnterapplyService;
use HfPayBundle\Services\HfpayLedgerConfigService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\OrderProfitSharing;
use OrdersBundle\Entities\OrderProfitSharingDetails;
use OrdersBundle\Entities\Trade;
use PaymentBundle\Services\Payments\HfPayService;
use PopularizeBundle\Services\BrokerageService;

/**
 * Class OrderProfitSharingService
 * @package OrdersBundle\Services
 *
 * 跑批处理需要进行分账的订单
 */
class OrderProfitSharingService
{
    private $normalOrdersRepository;
    private $orderProfitSharingRepository;
    private $normalOrdersItemsRepository;
    private $distributorRepository;
    private $orderProfitSharingDetailsRepository;
    private $aftersalesRefundRepository;

    public function __construct()
    {
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->orderProfitSharingRepository = app('registry')->getManager('default')->getRepository(OrderProfitSharing::class);
        $this->normalOrdersItemsRepository = app('registry')->getmanager('default')->getRepository(NormalOrdersItems::class);
        $this->distributorRepository = app('registry')->getmanager('default')->getRepository(Distributor::class);
        $this->orderProfitSharingDetailsRepository = app('registry')->getmanager('default')->getRepository(OrderProfitSharingDetails::class);
        $this->aftersalesRefundRepository = app('registry')->getmanager('default')->getRepository(AftersalesRefund::class);
    }

    /**
     *   订单分账处理
     */
    public function lists()
    {
        //订单状态为确认收货，分账状态为 分账, 支付渠道为汇付天下，订单售后时效时间已过
        $filter = [
            'order_status' => 'DONE',
            'pay_type' => 'hfpay',
            'is_profitsharing' => 2,
            'profitsharing_status' => 1,
            'order_auto_close_aftersales_time|lte' => time()
        ];
        $data = $this->normalOrdersRepository->getList($filter, 0, 500);
        if (empty($data)) {
            return true;
        }

        foreach ($data as $key => $val) {
            $distributor_id = $val['distributor_id'];
            $company_id = $val['company_id'];
            $order_id = $val['order_id'];
            $total_fee = $val['total_fee'];
            $pay_type = $val['pay_type'];
            $is_distribution = $val['is_distribution'];

            $account = $this->getOrderHfAccount($company_id, $distributor_id, $order_id);
            if ($account['code'] == false) {
                continue;
            }
            $is_sys = $account['is_sys'];
            $sys_user_cust_id = $account['sys_user_cust_id'];
            $sys_acct_id = $account['sys_acct_id'];
            $user_cust_id = $account['user_cust_id'];
            $acct_id = $account['acct_id'];

            //获取当前订单的退款金额
            $refundFilter['company_id'] = $company_id;
            $refundFilter['order_id'] = $order_id;
            $refundFilter['refund_status'] = ['SUCCESS', 'AUDIT_SUCCESS'];
            $refund_fee = $this->aftersalesRefundRepository->sum($refundFilter, 'sum(refund_fee)');

            //获取推广员佣金
            $rebate = 0; //单位分
            if ($is_distribution == 1) {
                $brokerage_filter['company_id'] = $company_id;
                $brokerage_filter['order_id'] = $order_id;
                $brokerage_filter['is_close'] = true;
                $brokerageService = new BrokerageService();
                $brokerage_list = $brokerageService->lists($brokerage_filter, ["created" => "DESC"]);
                if (!empty($brokerage_list['list'])) {
                    foreach ($brokerage_list['list'] as $brokerage_key => $brokerage_val) {
                        $rebate += $brokerage_val['rebate'];
                    }
                }
            }

            //手续费费率
            $fee_rate = bcdiv($val['profitsharing_rate'] ?? 0, 100);
            //手续费金额
            $fee_amt = 0;
            //门店设置的手续费率
            // $distributor_info = $this->distributorRepository->getInfoById($distributor_id);
            // if (!empty($distributor_info['rate'])) {
            //     $fee_rate = $distributor_info['rate'];
            // }
            //平台手续费费率（如果门店未设置费率则使用平台费率）
            // if (empty($fee_rate)) {
            //     $hfpay_ledger_config_service = new HfpayLedgerConfigService();
            //     $hfpay_config_info           = $hfpay_ledger_config_service->getLedgerConfig(['company_id' => $company_id]);
            //     if (!empty($hfpay_config_info['rate'])) {
            //         $fee_rate = $hfpay_config_info['rate'];
            //     }
            // }

            //计算店铺实际可获得金额
            $total_fee = $total_fee - $refund_fee;
            //门店收入
            $distributor_fee = $total_fee;
            if (!empty($fee_rate)) {
                $fee_amt = bcdiv(bcmul($total_fee, $fee_rate), 100);
                if ($fee_amt >= 1) {
                    $distributor_fee -= $fee_amt;
                }
            }
            if ($rebate > 0) {
                $distributor_fee -= $rebate;
            }
            if ($distributor_fee < 0) {
                $distributor_fee = 0;
            }

            //生成订单分账记录
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                $order_profit_sharing_id = []; //存储分账记录id

                $params = [
                    'company_id' => $company_id,
                    'order_id' => $order_id,
                    'distributor_id' => $distributor_id,
                    'pay_type' => $pay_type,
                    'channel_id' => $user_cust_id,
                    'channel_acct_id' => $acct_id,
                    'total_fee' => $total_fee,
                ];
                $reslut = $this->orderProfitSharingRepository->create($params);

                //分账
                $sharingDetails = [];
                if ($account['is_open'] == 'true' && !empty($sys_user_cust_id) && !empty($sys_acct_id)) {
                    //分账给平台（交易手续费费用）
                    $sharingDetails[] = [
                        'sharing_id' => $reslut['order_profit_sharing_id'],
                        'company_id' => $company_id,
                        'distributor_id' => 0,
                        'order_id' => $order_id,
                        'channel_id' => $sys_user_cust_id,
                        'channel_acct_id' => $sys_acct_id,
                        'total_fee' => $fee_amt + $rebate,
                    ];
                }

                //店铺
                $sharingDetails[] = [
                    'sharing_id' => $reslut['order_profit_sharing_id'],
                    'company_id' => $company_id,
                    'distributor_id' => $distributor_id,
                    'order_id' => $order_id,
                    'channel_id' => $user_cust_id,
                    'channel_acct_id' => $acct_id,
                    'total_fee' => $distributor_fee,
                ];

                foreach ($sharingDetails as $saveData) {
                    $this->orderProfitSharingDetailsRepository->create($saveData);
                }

                $order_profit_sharing_id[] = $reslut['order_profit_sharing_id'];

                //修改订单分账状态
                $order_filter = [
                    'company_id' => $company_id,
                    'order_id' => $order_id,
                ];
                $update_info = [
                    'profitsharing_status' => 2
                ];
                $this->normalOrdersRepository->update($order_filter, $update_info);
                //事物提交
                $conn->commit();

                //触发汇付分账处理事件
                $eventData = [
                    'order_id' => $order_id,
                    'order_profit_sharing_id' => $order_profit_sharing_id
                ];
                event(new HfpayProfitSharingEvent($eventData));
            } catch (\Exception $e) {
                app('log')->debug('hf_profit_data =>'.var_export($e->getMessage(), 1));
                $conn->rollback();
                continue;
            } catch (\Throwable $e) {
                $conn->rollback();
                continue;
            }
        }
    }

    /**
     *   订单分账初始化
     */
    public function initLists($companyId)
    {
        $filter = [
            'company_id' => $companyId,
        ];
        $reslut = $this->orderProfitSharingRepository->lists($filter);

        if ($reslut['list']) {
            foreach ($reslut['list'] as $v) {
                $sharingDetails = [
                    'sharing_id' => $v['order_profit_sharing_id'],
                    'company_id' => $companyId,
                    'distributor_id' => $v['distributor_id'],
                    'order_id' => $v['order_id'],
                    'channel_id' => $v['channel_id'],
                    'channel_acct_id' => $v['channel_acct_id'],
                    'total_fee' => $v['total_fee'],
                ];
                $this->orderProfitSharingDetailsRepository->create($sharingDetails);
            }
        }
    }

    /**
     * @param $company_id
     * @param $distributor_id
     * @param $order_id
     * @return array|bool
     *
     * 获取订单汇付分账账户
     */
    private function getOrderHfAccount($company_id, $distributor_id, $order_id)
    {
        //平台汇付账户
        $service = new HfPayService();
        $hfpay_setting_info = $service->getPaymentSetting($company_id);
        $is_open = $hfpay_setting_info['is_open'] ?? 'false';
        $sys_user_cust_id = $hfpay_setting_info['mer_cust_id'] ?? '';
        $sys_acct_id = $hfpay_setting_info['acct_id'] ?? '';

        //查找门店汇付账号
        $hfpay_enterapply_service = new HfpayEnterapplyService();
        $filter = [
            'company_id' => $company_id,
            'distributor_id' => $distributor_id,
            'status' => 3
        ];
        $distributor_enter_apply = $hfpay_enterapply_service->getEnterapply($filter);
        //如果门店未申请汇付账户，支付时间超过160天则分账给平台账户
        if (empty($distributor_enter_apply)) {
            if ($is_open == 'false' || empty($sys_user_cust_id) || empty($sys_acct_id)) {
                return [
                    'code' => false
                ];
            }
            //获取交易单信息
            $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
            $filter = [
                'company_id' => $company_id,
                'order_id' => $order_id,
            ];
            $trade = $tradeRepository->getTradeList($filter);
            if ($trade['list']) {
                $trade_info = $trade['list'][0];
            }

            $pay_date = $trade_info['payDate'];
            $max_profit_sharing_date = strtotime("$pay_date +160 day");
            app('log')->debug('门店未开通汇付收款账户，平台分账时间：' . $max_profit_sharing_date);
            if (($max_profit_sharing_date - time()) > 0) {
                return [
                    'code' => false
                ];
            }

            $user_cust_id = $sys_user_cust_id;
            $acct_id = $sys_acct_id;
            $is_sys = 1;
        } else {
            $user_cust_id = $distributor_enter_apply['user_cust_id'];
            $acct_id = $distributor_enter_apply['acct_id'];
            $is_sys = 2;
        }

        return [
            'code' => true,
            'is_open' => $is_open,
            'is_sys' => $is_sys,
            'user_cust_id' => $user_cust_id,
            'acct_id' => $acct_id,
            'sys_user_cust_id' => $sys_user_cust_id,
            'sys_acct_id' => $sys_acct_id
        ];
    }

    /**
     * 获取已结算资金
     */
    public function getProfitShareCapital($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $criteria->select('sum(b.total_fee)')
            ->from('order_profit_sharing', 'a')
            ->innerJoin('a', 'order_profit_sharing_details', 'b', 'a.company_id = b.company_id and a.order_profit_sharing_id = b.sharing_id');

        $order = [ 'company_id', 'create_time', 'pay_type', 'order_id'];

        if ($filter) {
            foreach ($filter as $key => $filterValue) {
                if (is_array($filterValue)) {
                    array_walk($filterValue, function (&$value) use ($criteria) {
                        $value = $criteria->expr()->literal($value);
                    });
                } else {
                    $filterValue = $criteria->expr()->literal($filterValue);
                }
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $v = in_array($v, $order) ? 'a.'.$v : $v;
                    if ($v == 'distributor_id') {
                        $v = 'b.'.$v;
                    }
                    $criteria->andWhere($criteria->expr()->andX(
                        $criteria->expr()->$k($v, $filterValue)
                    ));
                    continue;
                } elseif (is_array($filterValue)) {
                    $key = in_array($key, $order) ? 'a.'.$key : $key;
                    if ($key == 'distributor_id') {
                        $key = 'b.'.$key;
                    }
                    $criteria->andWhere($criteria->expr()->andX(
                        $criteria->expr()->in($key, $filterValue)
                    ));
                    continue;
                } else {
                    $key = in_array($key, $order) ? 'a.'.$key : $key;
                    if ($key == 'distributor_id') {
                        $key = 'b.'.$key;
                    }
                    $criteria->andWhere($criteria->expr()->andX(
                        $criteria->expr()->eq($key, $filterValue)
                    ));
                }
            }
        }
        $sum = $criteria->execute()->fetchColumn();
        return intval($sum);
    }
}
