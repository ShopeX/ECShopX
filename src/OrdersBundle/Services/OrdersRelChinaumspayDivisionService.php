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

use OrdersBundle\Entities\OrdersRelChinaumspayDivision;
use Exception;

class OrdersRelChinaumspayDivisionService
{
    // 划付状态（待处理）
    public const STATUS_READY = 0;
    // 划付状态（已上传）
    public const STATUS_UPLOADED = 1;
    // 划付状态（无需处理）
    public const STATUS_SKIP = 2;

    public $ordersRelChinaumspayDivisionRepository;

    public function __construct()
    {
        $this->ordersRelChinaumspayDivisionRepository     = app('registry')->getManager('default')->getRepository(OrdersRelChinaumspayDivision::class);
    }

    /**
     * 创建银联商务支付，分账订单关联表
     * 用于记录订单分账、划付的状态
     * @param int    $companyId 
     * @param string $orderId  
     */
    public function addRelChinaumsPayDivision(int $companyId, string $orderId)
    {
        $data = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'status' => self::STATUS_READY,
        ];
        return $this->create($data);
    }

    /**
     * 获取需要划付的订单总条数
     */
    public function getNeedTransferCount()
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('orders_rel_chinaumspay_division', 'reldivision')
            ->leftJoin('reldivision', 'orders_normal_orders', 'o', 'reldivision.order_id = o.order_id');
        $criteria->where($criteria->expr()->eq('reldivision.status', self::STATUS_READY));
        $criteria->andWhere($criteria->expr()->lte('o.order_auto_close_aftersales_time', time()));
        $count = $criteria->execute()->fetchColumn();
        return $count;
    }

    /**
     * 获取需要划付的订单数据
     * @param  string $distributorId     店铺ID
     */
    public function getNeedTransferList($distributorId)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('reldivision.*, o.distributor_id, o.total_fee')
            ->from('orders_rel_chinaumspay_division', 'reldivision')
            ->leftJoin('reldivision', 'orders_normal_orders', 'o', 'reldivision.order_id = o.order_id');
        $criteria->where($criteria->expr()->eq('o.distributor_id', $distributorId));
        $criteria->andWhere($criteria->expr()->eq('reldivision.status', self::STATUS_READY));
        $criteria->andWhere($criteria->expr()->lte('o.order_auto_close_aftersales_time', time()));
        $lists = $criteria->execute()->fetchAll();
        return $lists ?? [];
    }

    /**
     * 获取需要划付的订单数据
     * @param  integer $page     当前页数
     * @param  integer $pageSize 每页条数
     */
    public function getNeedTransferDistributorList($companyId)
    {
        app('log')->info('companyId:'.$companyId);
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('o.distributor_id')
            ->from('orders_rel_chinaumspay_division', 'reldivision')
            ->leftJoin('reldivision', 'orders_normal_orders', 'o', 'reldivision.order_id = o.order_id');
        $criteria->where($criteria->expr()->eq('o.company_id', $companyId));
        $criteria->andWhere($criteria->expr()->eq('reldivision.status', self::STATUS_READY));
        $criteria->andWhere($criteria->expr()->lte('o.order_auto_close_aftersales_time', time()));
        $criteria->groupBy('distributor_id');
        $lists = $criteria->execute()->fetchAll();
        return $lists ?? [];
    }

    /**
     * Dynamically call the OrdersBranchService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->ordersRelChinaumspayDivisionRepository->$method(...$parameters);
    }
}
