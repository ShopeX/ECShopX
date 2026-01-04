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

namespace HfPayBundle\Traits;

trait HfpayStatisticsTrait
{
    /**
     * 总收入
     */
    public function income($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $row = 'a.profitsharing_rate, b.pay_fee as total_fee';
        $criteria->select('count(*)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'trade', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);

        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);

        $income = 0;
        $total_fee = 0;
        $fee_amt = 0;
        if ($totalCount) {
            $pageSize = 1000;
            $totalPage = ceil($totalCount / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
                $orderList = $criteria->select($row)->execute()->fetchAll();
                foreach ($orderList as $v) {
                    $order_total_fee = $v['total_fee'];
                    $profitsharing_rate = $v['profitsharing_rate'];
                    $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 10000);
                    if ($order_fee_amt >= 1) {
                        $fee_amt += $order_fee_amt;
                    }
                    $total_fee += $order_total_fee;
                }
            }
        }

        $income = $total_fee - $fee_amt;
        return [$fee_amt, $income];
    }

    /**
     * 总退款
     */
    public function refund($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $criteria->select('count(*)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'aftersales_refund', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);

        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);

        if ($filter['refund_status'] == 'SUCCESS') {
            $row = 'b.refunded_fee as total_fee';
        } else {
            $row = 'b.refund_fee as total_fee';
        }
        $row = 'a.profitsharing_rate, '. $row;
        $refund = 0;
        $refund_total_fee = 0;
        $refund_fee_amt = 0;
        if ($totalCount) {
            $pageSize = 1000;
            $totalPage = ceil($totalCount / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
                $orderList = $criteria->select($row)->execute()->fetchAll();
                foreach ($orderList as $v) {
                    $order_total_fee = $v['total_fee'];
                    $profitsharing_rate = $v['profitsharing_rate'];
                    $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 10000);
                    if ($order_fee_amt >= 1) {
                        $refund_fee_amt += $order_fee_amt;
                    }
                    $refund_total_fee += $order_total_fee;
                }
            }
        }
        $refund = $refund_total_fee - $refund_fee_amt;
        return [$refund_fee_amt, $refund];
    }

    /**
     * 交易总笔数
     */
    public function orderCount($companyId, $filter)
    {
        $filter['company_id'] = $companyId;
        $filter['is_profitsharing'] = 2;
        $filter['pay_type'] = 'hfpay';

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
        $criteria->select('count(*)')
            ->from('orders_normal_orders', 'a');

        $criteria = $this->getFilter($filter, $criteria);
        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);
        return $totalCount;
    }

    /**
     * 总计交易金额
     */
    public function orderTotalFee($companyId, $filter)
    {
        $filter['company_id'] = $companyId;
        $filter['is_profitsharing'] = 2;
        $filter['pay_type'] = 'hfpay';
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
        $criteria->select('sum(total_fee)')
            ->from('orders_normal_orders', 'a');

        $criteria = $this->getFilter($filter, $criteria);
        $sum = $criteria->execute()->fetchColumn();
        return $sum ?? 0;
    }

    /**
     * 已退款总笔数
     */
    public function orderRefundCount($companyId, $filter)
    {
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }
        $filter['company_id'] = $companyId;
        $filter['is_profitsharing'] = 2;
        $filter['refund_status'] = ['SUCCESS'];
        $filter['pay_type'] = 'hfpay';
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
            ->innerJoin('a', 'aftersales_refund', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);
        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);
        return $totalCount;
    }

    /**
     * 退款总金额
     */
    public function orderRefundTotalFee($companyId, $filter)
    {
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }
        $filter['company_id'] = $companyId;
        $filter['is_profitsharing'] = 2;
        $filter['refund_status'] = ['SUCCESS', 'AUDIT_SUCCESS'];
        $filter['pay_type'] = 'hfpay';
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

        $criteria->select('sum(b.refunded_fee)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'aftersales_refund', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);

        $sum = $criteria->execute()->fetchColumn();
        return $sum ?? 0;
    }

    /**
     * 在退总笔数
     */
    public function orderRefundingCount($companyId, $filter)
    {
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }
        $filter['company_id'] = $companyId;
        $filter['is_profitsharing'] = 2;
        $filter['refund_status'] = ['AUDIT_SUCCESS', 'READY', 'PROCESSING'];
        $filter['pay_type'] = 'hfpay';
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
            ->innerJoin('a', 'aftersales_refund', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');

        $criteria = $this->getFilter($filter, $criteria);
        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);
        return $totalCount;
    }

    /**
     * 在退总金额
     */
    public function orderRefundingTotalFee($companyId, $filter)
    {
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }
        $filter['company_id'] = $companyId;
        $filter['is_profitsharing'] = 2;
        $filter['refund_status'] = ['AUDIT_SUCCESS', 'READY', 'PROCESSING'];
        $filter['pay_type'] = 'hfpay';
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
        $criteria->select('sum(b.refund_fee)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'aftersales_refund', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');

        $criteria = $this->getFilter($filter, $criteria);
        $sum = $criteria->execute()->fetchColumn();
        return $sum ?? 0;
    }

    /**
     * 已结算手续费
     */
    public function orderProfitSharingCharge($companyId, $filter)
    {
        $filter['company_id'] = $companyId;
        $filter['is_profitsharing'] = 2;
        $filter['pay_type'] = 'hfpay';
        $filter['status'] = 1;
        if ($filter['distributor_id'] ?? 0) {
            $filter['b.distributor_id'] = $filter['distributor_id'] ?? 0;
            unset($filter['distributor_id']);
        }
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

        $row = 'a.profitsharing_rate, b.total_fee';
        $criteria->select('count(*)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'order_profit_sharing', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);

        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);

        $fee_amt = 0;
        if ($totalCount) {
            $pageSize = 1000;
            $totalPage = ceil($totalCount / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
                $orderList = $criteria->select($row)->execute()->fetchAll();
                foreach ($orderList as $v) {
                    $order_total_fee = $v['total_fee'];
                    $profitsharing_rate = $v['profitsharing_rate'];
                    $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 10000);
                    if ($order_fee_amt >= 1) {
                        $fee_amt += $order_fee_amt;
                    }
                }
            }
        }
        return $fee_amt;
    }

    /**
     * 总手续费（包含已退款）
     */
    public function orderTotalCharge($companyId, $filter)
    {
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }
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

        $row = 'a.profitsharing_rate, b.pay_fee as total_fee';
        $criteria->select('count(*)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'trade', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);

        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);

        $fee_amt = 0;
        if ($totalCount) {
            $pageSize = 1000;
            $totalPage = ceil($totalCount / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
                $orderList = $criteria->select($row)->execute()->fetchAll();
                foreach ($orderList as $v) {
                    $order_total_fee = $v['total_fee'];
                    $profitsharing_rate = $v['profitsharing_rate'];
                    $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 10000);
                    if ($order_fee_amt >= 1) {
                        $fee_amt += $order_fee_amt;
                    }
                }
            }
        }

        return $fee_amt;
    }

    /**
     * 总退款手续费
     */
    public function orderRefundTotalCharge($companyId, $filter)
    {
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }
        $filter['company_id'] = $companyId;
        $filter['pay_type'] = 'hfpay';
        $filter['is_profitsharing'] = 2;
        $filter['refund_status'] = 'SUCCESS';
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

        $criteria->select('count(*)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'aftersales_refund', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);

        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);

        $row = 'a.profitsharing_rate, b.refunded_fee as total_fee';

        $refund_fee_amt = 0;
        if ($totalCount) {
            $pageSize = 1000;
            $totalPage = ceil($totalCount / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
                $orderList = $criteria->select($row)->execute()->fetchAll();
                foreach ($orderList as $v) {
                    $order_total_fee = $v['total_fee'];
                    $profitsharing_rate = $v['profitsharing_rate'];
                    $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 10000);
                    if ($order_fee_amt >= 1) {
                        $refund_fee_amt += $order_fee_amt;
                    }
                }
            }
        }

        return $refund_fee_amt;
    }

    /**
     * 未结算手续费(包含已退款)
     */
    public function orderUnProfitSharingCharge($companyId, $filter)
    {
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }
        $filter['company_id'] = $companyId;
        $filter['pay_type'] = 'hfpay';
        $filter['is_profitsharing'] = 2;
        $filter['profitsharing_status'] = 1;
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

        $row = 'a.profitsharing_rate, b.pay_fee as total_fee';
        $criteria->select('count(*)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'trade', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);

        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);

        $fee_amt = 0;
        if ($totalCount) {
            $pageSize = 1000;
            $totalPage = ceil($totalCount / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
                $orderList = $criteria->select($row)->execute()->fetchAll();
                foreach ($orderList as $v) {
                    $order_total_fee = $v['total_fee'];
                    $profitsharing_rate = $v['profitsharing_rate'];
                    $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 10000);
                    if ($order_fee_amt >= 1) {
                        $fee_amt += $order_fee_amt;
                    }
                }
            }
        }

        return $fee_amt;
    }

    /**
     * 未结算已退款手续费
     */
    public function orderUnProfitSharingRefundTotalCharge($companyId, $filter)
    {
        if (isset($filter['order_status'])) {
            unset($filter['order_status']);
        }
        $filter['company_id'] = $companyId;
        $filter['pay_type'] = 'hfpay';
        $filter['is_profitsharing'] = 2;
        $filter['profitsharing_status'] = 1;
        $filter['refund_status'] = 'SUCCESS';
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

        $criteria->select('count(*)')
            ->from('orders_normal_orders', 'a')
            ->innerJoin('a', 'aftersales_refund', 'b', 'a.company_id = b.company_id and a.order_id = b.order_id');
        $criteria = $this->getFilter($filter, $criteria);

        $total = $criteria->execute()->fetchColumn();
        $totalCount = intval($total);

        $row = 'a.profitsharing_rate, b.refunded_fee as total_fee';

        $refund_fee_amt = 0;
        if ($totalCount) {
            $pageSize = 1000;
            $totalPage = ceil($totalCount / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
                $orderList = $criteria->select($row)->execute()->fetchAll();
                foreach ($orderList as $v) {
                    $order_total_fee = $v['total_fee'];
                    $profitsharing_rate = $v['profitsharing_rate'];
                    $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 10000);
                    if ($order_fee_amt >= 1) {
                        $refund_fee_amt += $order_fee_amt;
                    }
                }
            }
        }

        return $refund_fee_amt;
    }

    private function getFilter($filter, $criteria)
    {
        return parent::_where($filter, $criteria);
    }
}
