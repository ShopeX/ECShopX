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
use Dingo\Api\Exception\ResourceException;
use HfPayBundle\Traits\HfpayStatisticsTrait;
use OrdersBundle\Entities\OrderProfitSharing;
use OrdersBundle\Entities\OrderProfitSharingDetails;
use OrdersBundle\Services\TradeService;

class HfpayStatisticsService extends HfpayStatisticsBaseService
{
    use HfpayStatisticsTrait;

    private $aftersalesRefundRepository;

    private $orderProfitSharingRepository;

    private $orderProfitSharingDetailsRepository;

    public function __construct()
    {
        $this->aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $this->orderProfitSharingRepository = app('registry')->getManager('default')->getRepository(OrderProfitSharing::class);
        $this->orderProfitSharingDetailsRepository = app('registry')->getManager('default')->getRepository(OrderProfitSharingDetails::class);
    }

    /**
     * 总统计
     */
    public function count($companyId, $filter)
    {
        // ShopEx EcShopX Core Module
        $orderCount = $this->orderCount($companyId, $filter); //交易总笔数
        $orderTotalFee = $this->orderTotalFee($companyId, $filter); //总计交易金额
        $orderRefundCount = $this->orderRefundCount($companyId, $filter); //已退款总笔数
        $orderRefundTotalFee = $this->orderRefundTotalFee($companyId, $filter); //退款总金额
        $orderRefundingCount = $this->orderRefundingCount($companyId, $filter); //在退总笔数
        $orderRefundingTotalFee = $this->orderRefundingTotalFee($companyId, $filter); //在退总金额
        $orderProfitSharingCharge = $this->orderProfitSharingCharge($companyId, $filter); //已结算手续费
        $orderTotalCharge = $this->orderTotalCharge($companyId, $filter); //总手续费（包含已退款）
        $orderRefundTotalCharge = $this->orderRefundTotalCharge($companyId, $filter); //总退款手续费
        $orderUnProfitSharingTotalCharge = $this->orderUnProfitSharingCharge($companyId, $filter); //未结算手续费（包含已退款）
        $orderUnProfitSharingRefundTotalCharge = $this->orderUnProfitSharingRefundTotalCharge($companyId, $filter); //未结算已退款手续费

        return [
            'order_count' => $orderCount,
            'order_total_fee' => bcdiv($orderTotalFee, 100, 2),
            'order_refund_count' => $orderRefundCount,
            'order_refund_total_fee' => bcdiv($orderRefundTotalFee, 100, 2),
            'order_refunding_count' => $orderRefundingCount,
            'order_refunding_total_fee' => bcdiv($orderRefundingTotalFee, 100, 2),
            'order_profit_sharing_charge' => bcdiv($orderProfitSharingCharge, 100, 2),
            'order_total_charge' => bcdiv($orderTotalCharge, 100, 2),
            'order_refund_total_charge' => bcdiv($orderRefundTotalCharge, 100, 2),
            'order_un_profit_sharing_total_charge' => bcdiv($orderUnProfitSharingTotalCharge, 100, 2),
            'order_un_profit_sharing_refund_total_charge' => bcdiv($orderUnProfitSharingTotalCharge, 100, 2),
            'order_un_profit_sharing_charge' => bcdiv($orderUnProfitSharingTotalCharge - $orderUnProfitSharingRefundTotalCharge, 100, 2),
        ];
    }

    /**
     * 获取订单列表
     */
    public function getOrderList($companyId, $filter, $page = 1, $pageSize = 20, $orderBy = ['create_time' => 'desc'])
    {
        // TS: 53686f704578
        $res['total_count'] = $this->getOrderCount($companyId, $filter);
        $filter['company_id'] = $companyId;
        $filter['pay_type'] = 'hfpay';
        $filter['is_profitsharing'] = 2;
        $filter['trade_state'] = 'SUCCESS';
        if ($filter['start_date'] ?? null) {
            $filter['create_time|gte'] = $filter['start_date'];
            unset($filter['start_date']);
        }
        if ($filter['end_date'] ?? null) {
            $filter['create_time|lte'] = $filter['end_date'];
            unset($filter['end_date']);
        }
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->from('orders_normal_orders', 'a')
            ->leftJoin('a', 'distribution_distributor', 'b', 'a.distributor_id = b.distributor_id')
            ->leftJoin('a', 'trade', 't', 'a.order_id = t.order_id');

        if ($filter['order_status'] ?? '') {
            $criteria->leftJoin('a', 'aftersales_refund', 'c', 'a.order_id = c.order_id');
            //退款中
            if ($filter['order_status'] == 'refunding') {
                $filter['refund_status'] = ['AUDIT_SUCCESS', 'READY', 'PROCESSING'];
            }
            //退款成功
            if ($filter['order_status'] == 'refundsuccess') {
                $filter['order_status'] = '';
                $filter['refund_status'] = 'SUCCESS';
            }

            //退款失败
            if ($filter['order_status'] == 'refundfail') {
                // $filter['refund_status'] = ['READY', 'AUDIT_SUCCESS', 'SUCCESS', 'REFUSE', 'CANCEL', 'REFUNDCLOSE', 'PROCESSING'];
                //获取退款失败的订单
                // $criteria->andWhere($criteria->expr()->andX(
                //     $criteria->expr()->in('refund_status', ["'READY'", "'AUDIT_SUCCESS'", "'SUCCESS'", "'REFUSE'", "'CANCEL'", "'REFUNDCLOSE'", "'PROCESSING'"]),
                //     $criteria->expr()->isNull('refund_status')
                // ));

                $orderIds = $this->getRefundOrder($filter);
                if (empty($orderIds)) {
                    $res['total_count'] = 0;
                    $res['list'] = [];
                    return $res;
                }
                $filter['order_id'] = $orderIds;
            }

            //支付成功
            if ($filter['order_status'] == 'pay') {
                // $criteria = $criteria->andWhere(
                //     $criteria->expr()->orX(
                //         $criteria->expr()->in('refund_status', "'REFUSE', 'CANCEL', 'REFUNDCLOSE'"),
                //         $criteria->expr()->isNull('c.order_id')
                //     )
                // );

                $orderIds = $this->getRefundOrder($filter, 'pay');
                if ($orderIds) {
                    array_walk($orderIds, function (&$value) use ($criteria) {
                        $value = $criteria->expr()->literal($value);
                    });
                    $criteria = $criteria->andWhere(
                        $criteria->expr()->orX(
                            $criteria->expr()->in('a.order_id', $orderIds),
                            $criteria->expr()->isNull('c.order_id')
                        )
                    );
                } else {
                    $criteria = $criteria->andWhere(
                        $criteria->expr()->andX(
                            $criteria->expr()->isNull('c.order_id')
                        )
                    );
                }
            }
        }
        $criteria = $this->_where($filter, $criteria);

        $row = 'FROM_UNIXTIME(a.create_time) as create_time, t.trade_id, a.order_id, profitsharing_status, a.total_fee, IF(IFNULL((a.total_fee * (profitsharing_rate/10000)), 0) >= 1, a.total_fee * (profitsharing_rate/10000), 0) as charge, IFNULL(convert(profitsharing_rate/100,decimal(15,2)), 0) as profitsharing_rate, b.name as distributor_name, a.distributor_id, order_status, delivery_status, receipt_type, ziti_status, cancel_status, is_invoiced, app_pay_type';
        foreach ($orderBy as $columns => $value) {
            $criteria->orderBy($columns, $value);
        }
        $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
        $orderList = $criteria->select($row)->groupBy('a.order_id')->execute()->fetchAll();

        //计算退款金额
        foreach ($orderList as $key => &$value) {
            $refundFilter = [
                'company_id' => $companyId,
                'order_id' => $value['order_id'],
                'refund_status|notIn' => ['CANCEL'],
            ];
            //查询退款状态
            $refundStatus = $this->getRefundStatus($refundFilter);
            if ($refundStatus) {
                $value['order_status'] = $refundStatus;
            }

            $this->handelOrderStatus($value);

            $orderList[$key]['refund_fee'] = $this->aftersalesRefundRepository->sum([
                'company_id' => $companyId,
                'order_id' => $value['order_id'],
                'refund_status|notIn' => ['CANCEL', 'REFUSE', 'REFUNDCLOSE', 'CHANGE'],
            ], 'sum(refund_fee)');
            $orderList[$key]['app_pay_type_desc'] = config('order.appPayType')[$value['app_pay_type']];
        }
        $res['list'] = $orderList;

        return $res;
    }

    public function getRefundStatus($filter)
    {
        $refundList = $this->aftersalesRefundRepository->getList($filter)['list'];

        if ($refundList) {
            $refundStatus = array_column($refundList, 'refund_status');
            if (in_array('AUDIT_SUCCESS', $refundStatus) || in_array('READY', $refundStatus) || in_array('PROCESSING', $refundStatus)) {
                //退款中
                return 'refunding';
            }
            if (in_array('SUCCESS', $refundStatus)) {
                //退款成功
                return 'refundsuccess';
            }
            if (in_array('CHANGE', $refundStatus)) {
                //退款失败
                return 'refundfail';
            }
            if (in_array('REFUSE', $refundStatus) || in_array('CANCEL', $refundStatus) || in_array('REFUNDCLOSE', $refundStatus)) {
                //支付成功
                return 'pay';
            }
        } else {
            return 'pay';
        }
        return '';
    }

    /**
     * 获取退款的订单
     */
    public function getRefundOrder($filter, $refundStatus = 'refundfail')
    {
        $refundFilter = [
            'company_id' => $filter['company_id'],
            'pay_type' => 'hfpay',
        ];

        if ($filter['order_id'] ?? '') {
            $refundFilter['order_id'] = $filter['order_id'];
        }

        $orderIds = [];

        $refundList = $this->aftersalesRefundRepository->getList($refundFilter)['list'];
        $orderList = [];
        if ($refundList) {
            $refundStatusArr = ['READY', 'AUDIT_SUCCESS', 'SUCCESS', 'REFUSE', 'CANCEL', 'REFUNDCLOSE', 'PROCESSING'];
            $refundStatusArr1 = ['READY', 'AUDIT_SUCCESS', 'SUCCESS', 'PROCESSING', 'CHANGE'];
            foreach ($refundList as $value) {
                $orderList[$value['order_id']][] = $value['refund_status'];
            }
            foreach ($orderList as $order_id => $order) {
                if ($refundStatus == 'refundfail') {
                    for ($i = 0; $i < count($refundStatusArr); $i++) {
                        if (in_array($refundStatusArr[$i], $order)) {
                            continue 2;
                        }
                    }
                    $orderIds[] = $order_id;
                }
                if ($refundStatus == 'pay') {
                    for ($i = 0; $i < count($refundStatusArr1); $i++) {
                        if (in_array($refundStatusArr1[$i], $order)) {
                            continue 2;
                        }
                    }
                    $orderIds[] = $order_id;
                }
            }
        }

        return $orderIds;
    }

    public function getOrderCount($companyId, $filter)
    {
        $filter['company_id'] = $companyId;
        $filter['pay_type'] = 'hfpay';
        $filter['is_profitsharing'] = 2;
        $filter['trade_state'] = 'SUCCESS';
        if ($filter['start_date'] ?? null) {
            $filter['create_time|gte'] = $filter['start_date'];
            unset($filter['start_date']);
        }
        if ($filter['end_date'] ?? null) {
            $filter['create_time|lte'] = $filter['end_date'];
            unset($filter['end_date']);
        }

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(DISTINCT a.order_id)')
            ->from('orders_normal_orders', 'a')
            ->leftJoin('a', 'distribution_distributor', 'b', 'a.distributor_id = b.distributor_id')
            ->leftJoin('a', 'trade', 't', 'a.order_id = t.order_id');

        if ($filter['order_status'] ?? '') {
            $criteria->leftJoin('a', 'aftersales_refund', 'c', 'a.order_id = c.order_id');
            //退款中
            if ($filter['order_status'] == 'refunding') {
                $filter['refund_status'] = ['AUDIT_SUCCESS', 'READY', 'PROCESSING'];
            }

            //退款成功
            if ($filter['order_status'] == 'refundsuccess') {
                $filter['order_status'] = '';
                $filter['refund_status'] = 'SUCCESS';
            }

            //退款失败
            if ($filter['order_status'] == 'refundfail') {
                // $filter['refund_status'] = ['READY', 'AUDIT_SUCCESS', 'SUCCESS', 'REFUSE', 'CANCEL', 'REFUNDCLOSE', 'PROCESSING'];
                //获取退款失败的订单
                // $criteria->andWhere($criteria->expr()->andX(
                //     $criteria->expr()->in('refund_status', ["'READY'", "'AUDIT_SUCCESS'", "'SUCCESS'", "'REFUSE'", "'CANCEL'", "'REFUNDCLOSE'", "'PROCESSING'"]),
                //     $criteria->expr()->isNull('refund_status')
                // ));

                $orderIds = $this->getRefundOrder($filter);
                if (empty($orderIds)) {
                    $res['total_count'] = 0;
                    $res['list'] = [];
                    return $res;
                }
                $filter['order_id'] = $orderIds;
            }

            //支付成功
            if ($filter['order_status'] == 'pay') {
                // $criteria = $criteria->andWhere(
                //     $criteria->expr()->orX(
                //         $criteria->expr()->in('refund_status', "'REFUSE', 'CANCEL', 'REFUNDCLOSE'"),
                //         $criteria->expr()->isNull('c.order_id')
                //     )
                // );

                $orderIds = $this->getRefundOrder($filter, 'pay');
                if ($orderIds) {
                    array_walk($orderIds, function (&$value) use ($criteria) {
                        $value = $criteria->expr()->literal($value);
                    });
                    $criteria = $criteria->andWhere(
                        $criteria->expr()->orX(
                            $criteria->expr()->in('a.order_id', $orderIds),
                            $criteria->expr()->isNull('c.order_id')
                        )
                    );
                } else {
                    $criteria = $criteria->andWhere(
                        $criteria->expr()->andX(
                            $criteria->expr()->isNull('c.order_id')
                        )
                    );
                }
            }
        }
        $criteria = $this->_where($filter, $criteria);

        $total = $criteria->execute()->fetchColumn();
        //var_dump($criteria->getSql());

        return intval($total);
    }

    /**
     * 获取订单详情
     */
    public function getOrderDetail($companyId, $orderId)
    {
        $filter['company_id'] = $companyId;
        $filter['order_id'] = $orderId;
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $row = 'FROM_UNIXTIME(create_time, "%Y-%m-%d %H:%i:%s") as create_time, a.order_id, profitsharing_status, a.total_fee, IF(IFNULL((a.total_fee * (profitsharing_rate/10000)), 0) >= 1, a.total_fee * (profitsharing_rate/10000), 0) as charge, IFNULL(convert(profitsharing_rate/100,decimal(15,2)), 0) as profitsharing_rate, b.name as distributor_name, a.distributor_id, order_status, delivery_status, receipt_type, ziti_status, cancel_status, is_invoiced, app_pay_type';
        $criteria->select($row)
            ->from('orders_normal_orders', 'a')
            ->leftJoin('a', 'distribution_distributor', 'b', 'a.distributor_id = b.distributor_id');
        $criteria = $this->_where($filter, $criteria);
        $res = $criteria->execute()->fetch();

        if (!$res) {
            throw new ResourceException('订单不存在');
        }
        $res['app_pay_type_desc'] = config('order.appPayType')[$res['app_pay_type']];

        $refundFilter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'refund_status|notIn' => ['CANCEL'],
        ];

        //查询退款状态
        $refundStatus = $this->getRefundStatus($refundFilter);
        if ($refundStatus) {
            $res['order_status'] = $refundStatus;
        }

        $this->handelOrderStatus($res);

        //总退款中或退款成功金额
        $res['refund_fee'] = $this->aftersalesRefundRepository->sum([
            'company_id' => $companyId,
            'order_id' => $orderId,
            'refund_status|notIn' => ['CANCEL', 'REFUSE', 'REFUNDCLOSE', 'CHANGE'],
        ], 'sum(refund_fee)');

        //剩余金额
        $res['balance'] = $res['total_fee'] - $res['refund_fee'];

        $tradeService = new TradeService();
        //获取支付单
        $tradeInfo = $tradeService->getInfo([
            'company_id' => $companyId,
            'order_id' => $orderId,
            'trade_state' => 'SUCCESS',
        ]);
        //支付单号
        $res['trade_id'] = $tradeInfo ? $tradeInfo['trade_id'] : '';
        //支付时间
        $res['pay_time'] = $tradeInfo ? date('Y-m-d H:i:s', $tradeInfo['time_expire']) : '';

        //获取分账订单号
        $orderProfitSharingInfo = $this->orderProfitSharingRepository->getInfo([
            'company_id' => $companyId,
            'order_id' => $orderId,
        ]);
        $res['hf_order_id'] = $orderProfitSharingInfo ? $orderProfitSharingInfo['hf_order_id'] : '';
        $res['hf_order_date'] = $orderProfitSharingInfo ? date('Y-m-d H:i:s', strtotime($orderProfitSharingInfo['hf_order_date'])) : '';

        //获取退款列表
        $refundList = $this->aftersalesRefundRepository->getList($refundFilter)['list'];

        $refundListArr = [];
        if ($refundList) {
            foreach ($refundList as $v) {
                $fee_amt = bcdiv(bcmul($v['refund_fee'], $res['profitsharing_rate']), 100);
                $data = [
                    'refund_bn' => $v['refund_bn'],
                    'refund_id' => $v['refund_id'],
                    'refund_fee' => $fee_amt,
                    'refund_status' => $v['refund_status'],
                    'distributor_name' => '总部',
                ];
                $data1 = [
                    'refund_bn' => $v['refund_bn'],
                    'refund_id' => $v['refund_id'],
                    'refund_fee' => $v['refund_fee'] - $fee_amt,
                    'refund_status' => $v['refund_status'],
                    'distributor_name' => $res['distributor_name'],
                ];
                array_push($refundListArr, $data, $data1);
            }
        }
        $res['refund_list'] = $refundListArr;

        //分账信息列表
        $shareDetals = $this->orderProfitSharingDetailsRepository->getLists(['company_id' => $companyId, 'order_id' => $orderId]);
        $shareList = [];
        if ($shareDetals) {
            foreach ($shareDetals as $detail) {
                $shareList[] = [
                    'created_at' => $detail['created_at'],
                    'distributor_name' => $detail['distributor_id'] == 0 ? '总部' : $res['distributor_name'],
                    'total_fee' => $detail['total_fee'],
                ];
            }
        }
        $res['profit_share_list'] = $shareList;

        return $res;
    }

    /**
     * 处理订单状态
     */
    private function handelOrderStatus(&$orderInfo)
    {
        if ($orderInfo) {
            if (($orderInfo['order_status'] == 'CANCEL_WAIT_PROCESS' && $orderInfo['cancel_status'] == 'WAIT_PROCESS') ||
                ($orderInfo['order_status'] == 'CANCEL_WAIT_PROCESS' && $orderInfo['cancel_status'] == 'NO_APPLY_CANCEL') ||
                ($orderInfo['order_status'] == 'PAYED' && $orderInfo['cancel_status'] == 'WAIT_PROCESS')
            ) {
                $orderInfo['order_status'] = 'refunding';
            } elseif (($orderInfo['order_status'] == 'PAYED' && in_array($orderInfo['cancel_status'], ['NO_APPLY_CANCEL', 'FAILS'])) ||
                      ($orderInfo['order_status'] == 'PAYED' && $orderInfo['receipt_type'] == 'ziti' && $orderInfo['ziti_status'] == 'PENDING') ||
                      ($orderInfo['order_status'] == 'WAIT_BUYER_CONFIRM' && $orderInfo['delivery_status'] == 'DONE' && $orderInfo['receipt_type'] == 'logistics') ||
                      (in_array($orderInfo['order_status'], ['DONE', 'REVIEW_PASS']))
            ) {
                $orderInfo['order_status'] = 'pay';
            } elseif ($orderInfo['order_status'] == 'CANCEL' && $orderInfo['cancel_status'] == 'SUCCESS') {
                $orderInfo['order_status'] = 'refundsuccess';
            } elseif ($orderInfo['order_status'] == 'DONE' && $orderInfo['cancel_status'] == 'FAILS') {
                $orderInfo['order_status'] = 'refundfail';
            }
        }
        unset($orderInfo['delivery_status'], $orderInfo['receipt_type'], $orderInfo['ziti_status'], $orderInfo['cancel_status'], $orderInfo['is_invoiced']);
    }
}
