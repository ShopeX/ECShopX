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

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;

use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\StatementsService;
use SupplierBundle\Services\SupplierService;
use AftersalesBundle\Services\AftersalesService;
use SupplierBundle\Services\SupplierOrderService;
use OrdersBundle\Services\StatementDetailsService;
use DistributionBundle\Services\DistributorService;
use OrdersBundle\Services\Orders\NormalOrderService;

class GenerateStatementsJob extends Job
{
    private $companyId; //商户ID

    private $distributorId; //店铺ID

    private $period; //结算周期

    private $lastEndTime; //上次结算周期结束时间

    private $merchantType;//商家类型：店铺 or 供应商
    
    // 供应商ID。注意：这里的 supplier_id != 订单表的 supplier_id(订单表的supplier_id对应operators表的operator_id)
    // 这里的supplier_id是供应商表supplier的id
    private $supplierId; 

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $distributorId, $period, $lastEndTime, $merchantType = 'distributor')
    {
        $this->companyId = $companyId;
        $this->period = $period;
        $this->lastEndTime = $lastEndTime;
        $this->merchantType = $merchantType;
        if ($this->merchantType == 'supplier') {
            $this->supplierId = $distributorId;
        } else {
            $this->distributorId = $distributorId;
        }
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $startTime = $this->lastEndTime + 1;
        switch ($this->period[1]) {
            case 'day':
                $endTime = strtotime(date('Y-m-d H:i:s', $this->lastEndTime) .'+'.$this->period[0].' day');
                while ($endTime < time()) {
                    $result = $this->doGenerate($startTime, $endTime);
                    if (!$result) {
                        break;
                    }

                    $startTime = $endTime + 1;
                    $endTime = strtotime(date('Y-m-d H:i:s', $endTime) .'+'.$this->period[0].' day');
                }
                break;
            case 'week':                
                if (strtotime(date('Y-m-d H:i:s', $this->lastEndTime) .'+'.(7 - date('w', $this->lastEndTime)).' day') == $this->lastEndTime) {
                    $endTime = strtotime(date('Y-m-d H:i:s', $this->lastEndTime) .'+'.($this->period[0] * 7 + 7 - date('w', $this->lastEndTime)).' day');
                } else {
                    $endTime = strtotime(date('Y-m-d H:i:s', $this->lastEndTime) .'+'.($this->period[0] * 7 - date('w', $this->lastEndTime)).' day');
                }

                while ($endTime < time()) {
                    $result = $this->doGenerate($startTime, $endTime);
                    if (!$result) {
                        break;
                    }

                    $startTime = $endTime + 1;
                    $endTime = strtotime(date('Y-m-d H:i:s', $endTime) .'+'.$this->period[0].' week');
                }
                break;
            case 'month':
                if (strtotime(date('Y-m-01', $this->lastEndTime).' +1 month') - 1 == $this->lastEndTime) {
                    $endTime = strtotime(date('Y-m-01', $this->lastEndTime).' +'.($this->period[0] + 1).' month') - 1;
                } else {
                    $endTime = strtotime(date('Y-m-01', $this->lastEndTime).' +'.$this->period[0].' month') - 1;
                }

                while ($endTime < time()) {
                    $result = $this->doGenerate($startTime, $endTime);
                    if (!$result) {
                        break;
                    }

                    $startTime = $endTime + 1;
                    $endTime = strtotime(date('Y-m-01', $endTime) .'+'.($this->period[0] + 1).' month') - 1;
                }
                break;
        }

        return true;
    }

    private function doGenerate($startTime, $endTime) 
    {
        switch ($this->merchantType) {
            case 'supplier':
                $this->forSupplier($startTime, $endTime);
                break;
                
            default:
                $this->forDistributor($startTime, $endTime);
        }
        return true;
    }

    private function forSupplier($startTime, $endTime) 
    {
        $offset = 0;
        $limit = 500;
        
        $summarized = [
            'company_id' => $this->companyId,
            'merchant_id' => 0,
            'supplier_id' => $this->supplierId,
            'merchant_type' => 'supplier',
            'statement_no' => $this->genIdSupplier(),
            'order_num' => 0,
            'total_fee' => 0,
            'freight_fee' => 0,
            'intra_city_freight_fee' => 0,
            'rebate_fee' => 0,
            'refund_fee' => 0,
            'statement_fee' => 0,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'distributor_id' => 0,
        ];

        $summarizedService = new StatementsService();
        $detailService = new StatementDetailsService();
        $supplierOrderService = new SupplierOrderService();
        $supplierService = new SupplierService();
        $aftersalesService = new AftersalesService();
        $rs = $supplierService->repository->getInfoById($this->supplierId);
        $operator_id = $rs['operator_id'];

        try {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();

            $summarized = $summarizedService->entityRepository->create($summarized);

            do {
                $qb = $conn->createQueryBuilder();
                $list = $qb->select('t.trade_id,t.pay_type,
                o.order_id,o.total_fee,o.cost_fee,o.freight_fee,o.receipt_type,o.supplier_id,
                o.commission_fee,coalesce(o.point_fee, 0) as point_fee,o.item_fee,o.order_status,
                o.pay_status,
                coalesce(r.refund_fee, 0) as refund_fee,
                ono.order_auto_close_aftersales_time,ono.supplier_id')
                    ->from('supplier_order', 'o')
                    ->leftJoin('o', 'trade', 't', 'o.order_id = t.order_id')
                    ->leftJoin('o', '(select
                     order_id,sum(refund_fee) as refund_fee 
                     from aftersales_refund a 
                     where refund_status in("AUDIT_SUCCESS", "SUCCESS", "CHANGE") 
                     and supplier_id='.$operator_id.' 
                     group by order_id)', 'r', 'o.order_id = r.order_id')
                    ->leftJoin('o', 'orders_normal_orders', 'ono', 'ono.order_id = o.order_id')
                    ->andWhere($qb->expr()->eq('o.company_id', $this->companyId))
                    ->andWhere($qb->expr()->eq('o.supplier_id', $operator_id))
                    ->andWhere($qb->expr()->eq('t.trade_state', $qb->expr()->literal('SUCCESS')))
                    ->andWhere($qb->expr()->eq('o.is_settled', 0))
                    ->andWhere($qb->expr()->gt('ono.order_auto_close_aftersales_time', 0))
                    ->andWhere($qb->expr()->lt('ono.order_auto_close_aftersales_time', $endTime))
                    ->andWhere($qb->expr()->notIn('o.order_id', '(select order_id from aftersales_refund where refund_status in("READY", "PROCESSING") )'))
                    ->addOrderBy('o.create_time', 'ASC')
                    ->setFirstResult($offset)->setMaxResults($limit)
                    ->execute()->fetchAll();
                
                if (empty($list)) {
                    throw new \Exception("数据为空");
                }
                            
                $orderIds = array_column($list, 'order_id');
                // 订单商品购买数量
                $numList = $supplierOrderService->getOrderSupplierNum($orderIds, $this->companyId,$operator_id);
                $numMap = array_column($numList, null, 'order_id');
                
                // 退款信息
                $refundMap = [];
                foreach ($orderIds as $k => $v) {
                    $refundMap[$v] = $aftersalesService->getAftersaleRefundInfoByOrderId($v, $this->companyId, $operator_id);
                }

                $details = [];
                foreach ($list as $row) {
                    // 获取总售后数量
                    $refundNum = 0;
                    $refundNum = $aftersalesService->getOrderAppliedTotalNum( $this->companyId, $row['order_id']);
                    
                    $detail = [
                        'company_id' => $this->companyId,
                        'merchant_id' => 0,
                        'supplier_id' => $this->supplierId,
                        'statement_id' => $summarized['id'],
                        'statement_no' => $summarized['statement_no'],
                        'order_id' => $row['order_id'],
                        'total_fee' => $row['total_fee'], // 现金支付
                        'rebate_fee' => 0, //暂时不考虑
                        'refund_fee' => $refundMap[$row['order_id']]['refunded_fee'] ?? 0,
                        'pay_type' => $row['pay_type'],
                        'num' => $numMap[$row['order_id']]['num'] ?? 0, // 购买数量
                        'item_fee' => $row['item_fee'], // 销售总金额
                        'commission_fee' => $row['commission_fee'], // 佣金
                        'cost_fee' => $row['cost_fee'], // 结算金额
                        'point_fee' => $row['point_fee'], // 积分抵扣
                        'refund_num' => $refundNum, // 退货数量
                        'refund_point' => $refundMap[$row['order_id']]['refunded_point'] ?? 0, // 退款积分
                        'refund_cost_fee' => $refundMap[$row['order_id']]['refund_cost_fee'] ?? 0,
                    ];
                    if ($row['order_status'] == 'CANCEL' && $row['pay_status'] == 'PAYED') {
                        // 售前退款
                        $detail['refund_fee'] = $row['total_fee'];
                        $detail['refund_num'] = $numMap[$row['order_id']]['num'] ?? 0;
                        $detail['refund_point'] = $row['point_fee'];
                        $detail['refund_cost_fee'] = $row['cost_fee'];
                    } 
                    
                    if ($row['receipt_type'] == 'dada') {
                        $detail['freight_fee'] = 0;
                        $detail['intra_city_freight_fee'] = $row['freight_fee'];
                    } else {
                        $detail['freight_fee'] = $row['freight_fee'];
                        $detail['intra_city_freight_fee'] = 0;
                    }
                    //供应商按成本价结算
                    $detail['statement_fee'] = bcsub($row['cost_fee'], bcadd($row['refund_fee'], $detail['intra_city_freight_fee']));

                    $summarized['order_num'] += 1;
                    $summarized['total_fee'] += $detail['total_fee'];
                    $summarized['freight_fee'] += $detail['freight_fee'];
                    $summarized['intra_city_freight_fee'] += $detail['intra_city_freight_fee'];
                    $summarized['rebate_fee'] += $detail['rebate_fee'];
                    $summarized['refund_fee'] += $detail['refund_fee'];
                    $summarized['statement_fee'] += $detail['statement_fee'];
                    $summarized['point_fee'] += $detail['point_fee'];
                    $summarized['refund_num'] += $detail['refund_num'];
                    $summarized['refund_point'] += $detail['refund_point'];
                    $summarized['refund_cost_fee'] += $detail['refund_cost_fee'];

                    $details[] = $detail;
                }

                if (!empty($list)) {
                    $detailService->batchInsert($details);
                    $_filter = [
                        'company_id' => $this->companyId,
                        'supplier_id' => $operator_id,
                        'order_id' => array_column($list, 'order_id'),
                    ];
                    $supplierOrderService->repository->updateOneBy($_filter, ['is_settled' => 1]);
                }

                $offset += $limit;
            } while (count($list) == $limit);

            $summarizedService->updateOneBy(['id' => $summarized['id']], $summarized);

            $redisKey = 'supplier_statements_last_end_time:'.$this->companyId.'_'.$this->supplierId;
            app('redis')->set($redisKey, $endTime);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug('生成结算单失败：company_id_'.$this->companyId.' supplier_id_'.$this->distributorId.' '.$e->getMessage());
            app('log')->debug('生成结算单失败：company_id_'.$this->companyId.' supplier_id_'.$this->distributorId.' '.$e->getFile());
            app('log')->debug('生成结算单失败：company_id_'.$this->companyId.' supplier_id_'.$this->distributorId.' '.$e->getLine());
            return false;
        }

        return true;
    }

    private function forDistributor($startTime, $endTime) 
    {
        $offset = 0;
        $limit = 500;

        $distributorService = new DistributorService();
        $distributor = $distributorService->getInfoSimple(['company_id' => $this->companyId, 'distributor_id' => $this->distributorId]);

        $summarized = [
            'company_id' => $this->companyId,
            'merchant_id' => $distributor['merchant_id'] ?? 0,
            'distributor_id' => $this->distributorId,
            'merchant_type' => 'distributor',
            'statement_no' => $this->genId(),
            'order_num' => 0,
            'total_fee' => 0,
            'freight_fee' => 0,
            'intra_city_freight_fee' => 0,
            'rebate_fee' => 0,
            'refund_fee' => 0,
            'statement_fee' => 0,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        $summarizedService = new StatementsService();
        $detailService = new StatementDetailsService();
        $tradeService = new TradeService();
        $normalOrderService = new NormalOrderService();

        try {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();

            $summarized = $summarizedService->create($summarized);

            do {
                $qb = $conn->createQueryBuilder();
                $list = $qb->select('t.trade_id,o.order_id,o.total_fee,o.freight_fee,o.receipt_type,coalesce(r.refund_fee, 0) as refund_fee,t.pay_type')
                    ->from('orders_normal_orders', 'o')
                    ->leftJoin('o', 'trade', 't', 'o.order_id = t.order_id')
                    ->leftJoin('o', '(select order_id,sum(refund_fee) as refund_fee from aftersales_refund a where refund_status in("AUDIT_SUCCESS", "SUCCESS", "CHANGE") group by order_id)', 'r', 'o.order_id = r.order_id')
                    ->andWhere($qb->expr()->eq('o.company_id', $this->companyId))
                    ->andWhere($qb->expr()->eq('o.distributor_id', $this->distributorId))
                    ->andWhere($qb->expr()->eq('t.trade_state', $qb->expr()->literal('SUCCESS')))
                    ->andWhere($qb->expr()->neq('t.is_settled', 1))
                    ->andWhere($qb->expr()->gt('o.order_auto_close_aftersales_time', 0))
                    ->andWhere($qb->expr()->lt('o.order_auto_close_aftersales_time', $endTime))
                    ->andWhere($qb->expr()->notIn('o.order_id', '(select order_id from aftersales_refund where refund_status in("READY", "PROCESSING"))'))
                    ->addOrderBy('o.create_time', 'ASC')
                    ->setFirstResult($offset)->setMaxResults($limit)
                    ->execute()->fetchAll();

                $details = [];
                foreach ($list as $row) {
                    
                    //如果包含供应商商品，需要扣除商品的成本价。再结算给经销商
                    $costFee = 0;//商品成本金额，= 给供应商结算的金额
                    $orderItemsData = $normalOrderService->normalOrdersItemsRepository->getList([
                        'company_id' => $this->companyId,
                        'order_id' => $row['order_id'],
                        'supplier_id|gte' => 1,
                    ]);
                    if ($orderItemsData['list']) {
                        $costFee = array_sum(array_column($orderItemsData['list'], 'cost_fee'));
                    }
                    
                    $detail = [
                        'company_id' => $this->companyId,
                        'merchant_id' => $distributor['merchant_id'] ?? 0,
                        'distributor_id' => $this->distributorId,
                        'statement_id' => $summarized['id'],
                        'statement_no' => $summarized['statement_no'],
                        'order_id' => $row['order_id'],
                        'total_fee' => $row['total_fee'],
                        'rebate_fee' => 0, //暂时不考虑
                        'refund_fee' => $row['refund_fee'],
                        'pay_type' => $row['pay_type'],
                    ];
                    if ($row['receipt_type'] == 'dada') {
                        $detail['freight_fee'] = 0;
                        $detail['intra_city_freight_fee'] = $row['freight_fee'];
                    } else {
                        $detail['freight_fee'] = $row['freight_fee'];
                        $detail['intra_city_freight_fee'] = 0;
                    }
                    //结算金额，以分为单位
                    $detail['statement_fee'] = bcsub($row['total_fee'], bcadd($row['refund_fee'], $detail['intra_city_freight_fee']));
                    $detail['statement_fee'] = bcsub($detail['statement_fee'], $costFee);

                    $summarized['order_num'] += 1;
                    $summarized['total_fee'] += $detail['total_fee'];
                    $summarized['freight_fee'] += $detail['freight_fee'];
                    $summarized['intra_city_freight_fee'] += $detail['intra_city_freight_fee'];
                    $summarized['rebate_fee'] += $detail['rebate_fee'];
                    $summarized['refund_fee'] += $detail['refund_fee'];
                    $summarized['statement_fee'] += $detail['statement_fee'];

                    $details[] = $detail;
                }

                if (!empty($list)) {
                    $detailService->batchInsert($details);
                    $tradeService->updateBy(['trade_id' => array_column($list, 'trade_id')], ['is_settled' => 1]);
                }

                $offset += $limit;
            } while (count($list) == $limit);

            $summarizedService->updateOneBy(['id' => $summarized['id']], $summarized);
            $summarizedService->setLastEndTime($this->companyId, $this->distributorId, $endTime);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug('生成结算单失败：company_id_'.$this->companyId.' distributor_id_'.$this->distributorId.' '.$e->getMessage());
            return false;
        }

        return true;
    }

    private function genIdSupplier()
    {
        return date('Ymd').rand(1000, 9999).str_pad($this->supplierId % 10000, 4, '0', STR_PAD_LEFT);
    }

    private function genId()
    {
        return date('Ymd').rand(1000, 9999).str_pad($this->distributorId % 10000, 4, '0', STR_PAD_LEFT);
    }
}
