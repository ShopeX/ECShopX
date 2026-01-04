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

use AftersalesBundle\Entities\AftersalesRefund;
use HfPayBundle\Entities\HfpayTradeRecord;
use OrdersBundle\Entities\NormalOrders;
use PopularizeBundle\Services\BrokerageService;

/**
 * Class HfpayTradeRecordService
 * @package HfPayBundle\Services
 *
 * 需求文档:https://docs.qq.com/sheet/DVnlvQ3BocHRUdVNF?tab=BB08J2
 */
class HfpayTradeRecordService
{
    public const TRADE_TYPE_SUB = 0; //出账
    public const TRADE_TYPE_ADD = 1; //入账

    /** @var entityRepository */
    public $entityRepository;
    public $normalOrdersRepository;

    public $aftersalesRefundRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(HfpayTradeRecord::class);
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
    }

    /**
     * 汇付明细数据查询
     */
    public function lists($params)
    {
        $filter['company_id'] = $params['company_id'];
        if (!empty($params['distributor_id'])) {
            $filter['distributor_id'] = $params['distributor_id'];
        }
        if (!empty($params['order_id'])) {
            $filter['outer_order_id'] = $params['order_id'];
        }
        if (!empty($params['start_date'])) {
            $filter['trade_time|gte'] = strtotime($params['start_date']);
        }
        if (!empty($params['end_date'])) {
            $filter['trade_time|lte'] = strtotime($params['end_date']);
        }
        $result['total_count'] = 0;
        $result['list'] = [];
        $cols = 'outer_order_id, trade_time, trade_type, fin_type, income, outcome';
        $page = $params['page'];
        $page_size = $params['page_size'];
        $list = $this->entityRepository->lists($filter, $cols, $page, $page_size, ['trade_time' => 'desc']);
        if (empty($list['list'])) {
            return $result;
        }
        $fin_type = [
            '100' => '交易资金',
            '200' => '货款',
            '300' => '手续费',
            '310' => '手续费退回',
            '400' => '商户分销佣金发放',
            '401' => '商户分销佣金退回',
            '410' => '平台分销佣金发放',
            '411' => '平台分销佣金退回',
            '500' => '提现',
            '600' => '货款退回',
            '610' => '退款至消费者',
            '620' => '退款至消费者'
        ];
        $data = [];
        foreach ($list['list'] as $k => $v) {
            $data[] = [
                'trade_time' => date('Y-m-d H:i:s', $v['trade_time']),
                'order_id' => $v['outer_order_id'],
                'fin_type' => isset($fin_type[$v['fin_type']]) ? $fin_type[$v['fin_type']] : '',
                'income' => !empty($v['income']) ? bcdiv($v['income'], 100, 2) : 0,
                'outcome' => !empty($v['outcome']) ? bcdiv($v['outcome'], 100, 2) : 0
            ];
        }
        $result['list'] = $data;
        $result['total_count'] = $list['total_count'];

        return $result;
    }

    /**
     * 根据搜索条件查询汇付明细数据记录条数
     */
    public function count($filter)
    {
        $count = $this->entityRepository->count($filter);

        return $count;
    }

    /**
     * 导出列表数据
     */
    public function exportList($filter, $page, $limit)
    {
        $cols = 'trade_time, outer_order_id, trade_type, fin_type, income, outcome, is_clean, clean_time, message';
        $list = $this->entityRepository->getLists($filter, $cols, $page, $limit);

        return $list;
    }

    /**
     * @param $order_id
     * @return bool
     *
     * 支付成功
     */
    public function paySuccess($order_id)
    {
        $conn = app('registry')->getConnection('default');
        $sql = "SELECT * FROM orders_normal_orders where order_id = " . $order_id;
        $orderData = $conn->fetchAll($sql);
        if (empty($orderData['0'])) {
            return true;
        }
        $orderData = $orderData['0'];
        $pay_type = $orderData['pay_type'];
        $company_id = $orderData['company_id'];
        $distributor_id = $orderData['distributor_id'];
        $is_profitsharing = $orderData['is_profitsharing'];
        $total_fee = $orderData['total_fee'];
        $profitsharing_rate = $orderData['profitsharing_rate'];

        //判断是否是汇付订单
        if ($pay_type != 'hfpay') {
            return true;
        }

        $accounting_sheet = [];

        $accounting_sheet[] = [
            'trade_id' => $this->getTradeId(),
            'company_id' => $company_id,
            'distributor_id' => $distributor_id,
            'outer_order_id' => $order_id,
            'form_user_id' => $company_id,
            'target_user_id' => '1',
            'trade_time' => time(),
            'trade_type' => self::TRADE_TYPE_ADD,
            'fin_type' => '100',
            'income' => $total_fee,
            'outcome' => 0,
            'message' => '交易入账',
        ];
        //分账订单
        if ($is_profitsharing == 2) {
            $trade_id = $this->getTradeId();
            $accounting_sheet[] = [
                'trade_id' => $trade_id,
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'outer_order_id' => $order_id,
                'form_user_id' => $company_id,
                'target_user_id' => $distributor_id,
                'trade_time' => time(),
                'trade_type' => self::TRADE_TYPE_SUB,
                'fin_type' => '200',
                'income' => 0,
                'outcome' => $total_fee,
                'message' => '货款转账至商户账号',
            ];
            $accounting_sheet[] = [
                'trade_id' => $trade_id,
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'outer_order_id' => $order_id,
                'form_user_id' => $distributor_id,
                'target_user_id' => $company_id,
                'trade_time' => time(),
                'trade_type' => self::TRADE_TYPE_ADD,
                'fin_type' => '200',
                'income' => $total_fee,
                'outcome' => 0,
                'message' => '收到平台账号转账的货款',
            ];

            //手续费
            $fee_amt = bcdiv(bcmul($total_fee, $profitsharing_rate), 10000);
            if ($fee_amt >= 1) {
                $trade_id = $this->getTradeId();
                $accounting_sheet[] = [
                    'trade_id' => $trade_id,
                    'company_id' => $company_id,
                    'distributor_id' => $distributor_id,
                    'outer_order_id' => $order_id,
                    'form_user_id' => $company_id,
                    'target_user_id' => $distributor_id,
                    'trade_time' => time(),
                    'trade_type' => self::TRADE_TYPE_ADD,
                    'fin_type' => '300',
                    'income' => $fee_amt, //手续费
                    'outcome' => 0,
                    'message' => '收到商户交易服务费',
                ];
                $accounting_sheet[] = [
                    'trade_id' => $trade_id,
                    'company_id' => $company_id,
                    'distributor_id' => $distributor_id,
                    'outer_order_id' => $order_id,
                    'form_user_id' => $distributor_id,
                    'target_user_id' => $company_id,
                    'trade_time' => time(),
                    'trade_type' => self::TRADE_TYPE_SUB,
                    'fin_type' => '300',
                    'income' => 0,
                    'outcome' => $fee_amt,//手续费
                    'message' => '交易服务费转账至平台账号',
                ];
            }
        }

        if (!empty($accounting_sheet)) {
            $this->create($accounting_sheet);
        }

        return true;
    }

    /**
     * 佣金记录生成
     */
    public function brokerage($order_id)
    {
        $conn = app('registry')->getConnection('default');
        $sql = "SELECT * FROM orders_normal_orders where order_id = " . $order_id;
        $orderData = $conn->fetchAll($sql);
        if (empty($orderData['0'])) {
            return true;
        }
        $orderData = $orderData['0'];
        $pay_type = $orderData['pay_type'];
        $is_distribution = $orderData['is_distribution'];
        $company_id = $orderData['company_id'];
        $distributor_id = $orderData['distributor_id'];
        $is_profitsharing = $orderData['is_profitsharing'];

        //判断是否是汇付订单
        if ($pay_type != 'hfpay') {
            return true;
        }

        $accounting_sheet = [];
        //分销订单
        if ($is_distribution == 1) {
            $brokerage_filter['company_id'] = $company_id;
            $brokerage_filter['order_id'] = $order_id;
            $brokerageService = new BrokerageService();
            $brokerage_list = $brokerageService->lists($brokerage_filter, ["created" => "DESC"]);
            if (!empty($brokerage_list)) {
                foreach ($brokerage_list['list'] as $brokerage_key => $brokerage_val) {
                    if ($brokerage_val['rebate'] <= 0) {
                        continue;
                    }
                    $fin_type = '400'; //400 店铺承担佣金、410 平台承担佣金
                    if ($is_profitsharing == 1) {
                        $fin_type = '410';
                    }

                    $trade_id = $this->getTradeId();
                    $accounting_sheet[] = [
                        'trade_id' => $trade_id,
                        'company_id' => $company_id,
                        'distributor_id' => $distributor_id,
                        'outer_order_id' => $order_id,
                        'form_user_id' => $brokerage_val['user_id'],
                        'target_user_id' => $fin_type == 400 ? $distributor_id : $company_id,
                        'trade_time' => time(),
                        'trade_type' => self::TRADE_TYPE_ADD,
                        'fin_type' => $fin_type,
                        'income' => $brokerage_val['rebate'],
                        'outcome' => 0,
                        'message' => '收到商户分销佣金',
                    ];
                    $accounting_sheet[] = [
                        'trade_id' => $trade_id,
                        'company_id' => $company_id,
                        'distributor_id' => $distributor_id,
                        'outer_order_id' => $order_id,
                        'form_user_id' => $fin_type == 400 ? $distributor_id : $company_id,
                        'target_user_id' => $brokerage_val['user_id'],
                        'trade_time' => time(),
                        'trade_type' => self::TRADE_TYPE_SUB,
                        'fin_type' => $fin_type,
                        'income' => 0,
                        'outcome' => $brokerage_val['rebate'],
                        'message' => '发放分销佣金',
                    ];
                }
            }
        }

        if (!empty($accounting_sheet)) {
            $this->create($accounting_sheet);
        }
    }

    /**
     * 退款成功
     */
    public function refundSuccess($order_id, $refund_bn)
    {
        $conn = app('registry')->getConnection('default');
        $sql = "SELECT * FROM orders_normal_orders where order_id = " . $order_id;
        $orderData = $conn->fetchAll($sql);
        if (empty($orderData['0'])) {
            return true;
        }
        $orderData = $orderData['0'];
        $pay_type = $orderData['pay_type'];
        $company_id = $orderData['company_id'];
        $distributor_id = $orderData['distributor_id'];
        $is_profitsharing = $orderData['is_profitsharing'];
        $profitsharing_rate = $orderData['profitsharing_rate'];
        $total_fee = $orderData['total_fee'];

        //判断是否是汇付订单
        if ($pay_type != 'hfpay') {
            return true;
        }

        //获取退款金额
        $refund_filter = [
            'refund_bn' => $refund_bn
        ];
        $refund = $this->aftersalesRefundRepository->getInfo($refund_filter);
        if (empty($refund)) {
            return true;
        }
        $refund_fee = $refund['refund_fee'];

        $accounting_sheet = [];
        //非分账订单
        if ($is_profitsharing == 1) {
            $accounting_sheet[] = [
                'trade_id' => $this->getTradeId(),
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'outer_order_id' => $order_id,
                'form_user_id' => $company_id,
                'target_user_id' => '3',
                'trade_time' => time(),
                'trade_type' => self::TRADE_TYPE_SUB,
                'fin_type' => '620',
                'income' => 0,
                'outcome' => $refund_fee,
                'message' => '货款退还至消费者',
            ];
        }

        //分账订单
        if ($is_profitsharing == 2) {
            $trade_id = $this->getTradeId();
            $accounting_sheet[] = [
                'trade_id' => $trade_id,
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'outer_order_id' => $order_id,
                'form_user_id' => $distributor_id,
                'target_user_id' => $company_id,
                'trade_time' => time(),
                'trade_type' => self::TRADE_TYPE_SUB,
                'fin_type' => '600',
                'income' => 0,
                'outcome' => $refund_fee,
                'message' => '退款，货款退回平台',
            ];
            $accounting_sheet[] = [
                'trade_id' => $trade_id,
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'outer_order_id' => $order_id,
                'form_user_id' => $company_id,
                'target_user_id' => $distributor_id,
                'trade_time' => time(),
                'trade_type' => self::TRADE_TYPE_ADD,
                'fin_type' => '600',
                'income' => $refund_fee,
                'outcome' => 0,
                'message' => '收到商户货款退回',
            ];
            $accounting_sheet[] = [
                'trade_id' => $trade_id,
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'outer_order_id' => $order_id,
                'form_user_id' => $company_id,
                'target_user_id' => '3',
                'trade_time' => time(),
                'trade_type' => self::TRADE_TYPE_SUB,
                'fin_type' => '610',
                'income' => 0,
                'outcome' => $refund_fee,
                'message' => '货款退还至消费者',
            ];

            //手续费
            $fee_amt = bcdiv(bcmul($refund_fee, $profitsharing_rate), 10000);
            if ($fee_amt >= 1) {
                $trade_id = $this->getTradeId();
                $accounting_sheet[] = [
                    'trade_id' => $trade_id,
                    'company_id' => $company_id,
                    'distributor_id' => $distributor_id,
                    'outer_order_id' => $order_id,
                    'form_user_id' => $company_id,
                    'target_user_id' => $distributor_id,
                    'trade_time' => time(),
                    'trade_type' => self::TRADE_TYPE_SUB,
                    'fin_type' => '310',
                    'income' => 0,
                    'outcome' => $fee_amt, //手续费
                    'message' => '退还商户的平台手续费',
                ];
                $accounting_sheet[] = [
                    'trade_id' => $trade_id,
                    'company_id' => $company_id,
                    'distributor_id' => $distributor_id,
                    'outer_order_id' => $order_id,
                    'form_user_id' => $distributor_id,
                    'target_user_id' => $company_id,
                    'trade_time' => time(),
                    'trade_type' => self::TRADE_TYPE_ADD,
                    'fin_type' => '310',
                    'income' => $fee_amt,//手续费
                    'outcome' => 0,
                    'message' => '收到平台手续费退回',
                ];
            }
        }

        if (!empty($accounting_sheet)) {
            $this->create($accounting_sheet);
        }
        //全额退款更新为已结算
        $refunded_fee = $this->aftersalesRefundRepository->sum([
            'company_id' => $company_id,
            'order_id' => $order_id,
            'refund_status' => 'SUCCESS',
        ], 'sum(refund_fee)');

        $refund_fee = $refund_fee + $refunded_fee;
        if ($refund_fee >= $total_fee) {
            $filter = [
                'company_id' => $company_id,
                'order_id' => $order_id
            ];
            $updateInfo = [
                'profitsharing_status' => 2,
            ];
            $this->normalOrdersRepository->update($filter, $updateInfo);
        }
    }

    /**
     * 分账成功修改结算状态
     */
    public function profit($order_id)
    {
        //佣金明细退款佣金导入到汇付记账明细，商品涉及多次退款，统一在订单确认收货或全部退款完成时导入退款的佣金明细
        $conn = app('registry')->getConnection('default');
        $sql = "SELECT * FROM orders_normal_orders where order_id = " . $order_id;
        $orderData = $conn->fetchAll($sql);
        if (empty($orderData['0'])) {
            return true;
        }
        $orderData = $orderData['0'];
        $pay_type = $orderData['pay_type'];
        $is_distribution = $orderData['is_distribution'];
        $company_id = $orderData['company_id'];
        $distributor_id = $orderData['distributor_id'];
        $is_profitsharing = $orderData['is_profitsharing'];

        //判断是否是汇付订单
        if ($pay_type != 'hfpay') {
            return true;
        }

        $accounting_sheet = [];
        //分销订单
        if ($is_distribution == 1) {
            $brokerage_filter['company_id'] = $company_id;
            $brokerage_filter['order_id'] = $order_id;
            $brokerageService = new BrokerageService();
            $brokerage_list = $brokerageService->lists($brokerage_filter, ["created" => "DESC"]);
            if (!empty($brokerage_list)) {
                foreach ($brokerage_list['list'] as $brokerage_key => $brokerage_val) {
                    if ($brokerage_val['rebate'] > 0) {
                        continue;
                    }
                    $fin_type = '401'; //401 佣金退还给店铺、411 佣金
                    if ($is_profitsharing == 1) {
                        $fin_type = '411';
                    }

                    $trade_id = $this->getTradeId();
                    $accounting_sheet[] = [
                        'trade_id' => $trade_id,
                        'company_id' => $company_id,
                        'distributor_id' => $distributor_id,
                        'outer_order_id' => $order_id,
                        'form_user_id' => $fin_type == '401' ? $distributor_id : $company_id,
                        'target_user_id' => $brokerage_val['user_id'],
                        'trade_time' => time(),
                        'trade_type' => self::TRADE_TYPE_ADD,
                        'fin_type' => $fin_type,
                        'income' => abs($brokerage_val['rebate']),
                        'outcome' => 0,
                        'message' => '收到分销员佣金退回',
                    ];
                    $accounting_sheet[] = [
                        'trade_id' => $trade_id,
                        'company_id' => $company_id,
                        'distributor_id' => $distributor_id,
                        'outer_order_id' => $order_id,
                        'form_user_id' => $brokerage_val['user_id'],
                        'target_user_id' => $fin_type == '401' ? $distributor_id : $company_id,
                        'trade_time' => time(),
                        'trade_type' => self::TRADE_TYPE_SUB,
                        'fin_type' => $fin_type,
                        'income' => 0,
                        'outcome' => abs($brokerage_val['rebate']),
                        'message' => '分销员佣金退回至商户',
                    ];
                }
            }
        }
        if (!empty($accounting_sheet)) {
            $this->create($accounting_sheet);
        }

        //修改结算状态
        $filter = [
            'company_id' => $company_id,
            'outer_order_id' => $order_id,
        ];
        //判断数据是否存在
        $data = $this->entityRepository->getInfo($filter);
        if (!empty($data)) {
            $data = [
                'is_clean' => 1,
                'clean_time' => time()
            ];
            $this->entityRepository->updateBy($filter, $data);
        }
    }

    /**
     * @param $company_id
     * @param $distributor_id
     * @param $outcome
     *
     * 提现成功
     */
    public function withdraw($company_id, $distributor_id, $outcome, $order_id)
    {
        $accounting_sheet[] = [
            'trade_id' => $this->getTradeId(),
            'company_id' => $company_id,
            'distributor_id' => $distributor_id,
            'outer_order_id' => $order_id,
            'form_user_id' => $distributor_id,
            'target_user_id' => '2',
            'trade_time' => time(),
            'trade_type' => self::TRADE_TYPE_SUB,
            'fin_type' => '500',
            'income' => 0,
            'outcome' => $outcome,
            'message' => '提现',
            'is_clean' => 1,
            'clean_time' => time()
        ];

        $this->create($accounting_sheet);
    }

    /**
     * @param $data
     * 创建数据
     */
    public function create($data)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($data as $k => $v) {
                $this->entityRepository->create($v);
            }

            //事物提交
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
        } catch (\Throwable $e) {
            $conn->rollback();
        }
    }

    /**
     * @return mixed
     * 生成业务单号
     */
    public function getTradeId()
    {
        $redisId = app('redis')->incr('hfpay_trade_record_trade_id');
        app('redis')->expire('hfpay_trade_record_trade_id', strtotime(date('Y-m-d 23:59:59', time())));
        $max_length = 9;

        return 'T' . date('Ymd'). str_pad($redisId, $max_length, '0', STR_PAD_LEFT);
    }
}
