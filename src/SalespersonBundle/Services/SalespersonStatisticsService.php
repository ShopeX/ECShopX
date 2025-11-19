<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalespersonSalesStatistics;
use SalespersonBundle\Entities\SalespersonProfitStatistics;

use SalespersonBundle\Jobs\SalespersonStatisticsJob;

class SalespersonStatisticsService
{
    public $salespersonSalesStatisticsRepository;
    public $salespersonProfitStatisticsRepository;

    public function __construct()
    {
        $this->salespersonSalesStatisticsRepository = app('registry')->getManager('default')->getRepository(SalespersonSalesStatistics::class);
        $this->salespersonProfitStatisticsRepository = app('registry')->getManager('default')->getRepository(SalespersonProfitStatistics::class);
    }

    public function scheduleInitSalespersonStatistics()
    {
        $pageSize = 50;
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
             ->from('shop_salesperson')
             ->andWhere($criteria->expr()->eq('salesperson_type', $criteria->expr()->literal('shopping_guide')))
             ->andWhere($criteria->expr()->eq('is_valid', $criteria->expr()->literal('true')));
        $count = $criteria->execute()->fetchColumn();

        $totalPage = ceil($count / $pageSize);

        $yesterday = $this->yesterday();
        $yesterdayStartTime = $yesterday['start_time'];

        for ($i = 1; $i <= $totalPage; $i++) {
            $criteria = $conn->createQueryBuilder();
            $salespersons = $criteria
                ->select('ss.company_id, ss.salesperson_id, srs.shop_id')
                ->from('shop_salesperson', 'ss')
                ->leftJoin('ss', 'shop_rel_salesperson', 'srs', 'ss.salesperson_id = srs.salesperson_id')
                ->andWhere($criteria->expr()->eq('salesperson_type', $criteria->expr()->literal('shopping_guide')))
                ->andWhere($criteria->expr()->eq('is_valid', $criteria->expr()->literal('true')));

            if ($pageSize > 0) {
                $criteria->setFirstResult(($i - 1) * $pageSize)
                    ->setMaxResults($pageSize);
            }
            $lists = $criteria->execute()->fetchAll();
            foreach ($lists as $v) {
                $info = $this->salespersonSalesStatisticsRepository->getInfo(['salesperson_id' => $v['salesperson_id'], 'date' => date('Ymd', $yesterdayStartTime)]);
                if ($info) {
                    return true;
                }
                if ($v['shop_id']) {
                    $job = (new SalespersonStatisticsJob($v['company_id'], $v['shop_id'], $v['salesperson_id']))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
                }
            }
        }
    }

    /**
     * 导购统计信息
     *
     * @param int $companyId
     * @param int $distributorId
     * @param int $salespersonId
     * @return void
     */
    public function saveSalespersonStatisticsJob($companyId, $distributorId, $salespersonId)
    {
        $yesterday = $this->yesterday();
        $yesterdayStartTime = $yesterday['start_time'];
        $yesterdayEndTime = $yesterday['end_time'];
        $salespersonService = new SalespersonService();
        $result = $salespersonService->getCurrentMonthStatistics($companyId, $salespersonId, $yesterdayStartTime, $yesterdayEndTime);
        $redundResult = $salespersonService->refundOrderCountData($companyId, $salespersonId, $yesterdayStartTime, $yesterdayEndTime);
        $salespersonService->profitFee($companyId, $salespersonId, $yesterdayStartTime, $yesterdayEndTime);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $salesData = [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'salesperson_id' => $salespersonId,
                'date' => date('Ymd', $yesterdayStartTime),
                'popularize_order_fee' => $result['popularize_order_fee'] ?? 0,
                'popularize_order_count' => $result['popularize_order_count'] ?? 0,
                'offline_order_fee' => $result['offline_order_fee'] ?? 0,
                'offline_order_count' => $result['offline_order_count'] ?? 0,
                'total_refund_fee' => $result['total_refund_fee'] ?? 0,
                'total_refund_count' => $result['total_refund_count'] ?? 0,
            ];
            $this->salespersonSalesStatisticsRepository->create($salesData);
            $profitData = [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'salesperson_id' => $salespersonId,
                'date' => date('Ymd', $yesterdayStartTime),
                'unconfirmed_seller_fee' => $result['unconfirmed_seller_fee'] ?? 0,
                'confirm_seller_fee' => $result['confirm_seller_fee'] ?? 0,
                'unconfirmed_offline_seller_fee' => $result['unconfirmed_offline_seller_fee'] ?? 0,
                'confirm_offline_seller_fee' => $result['confirm_offline_seller_fee'] ?? 0,
                'unconfirmed_popularize_seller_fee' => $result['unconfirmed_popularize_seller_fee'] ?? 0,
                'confirm_popularize_seller_fee' => $result['confirm_popularize_seller_fee'] ?? 0,
            ];
            $this->salespersonProfitStatisticsRepository->create($profitData);
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 返回昨日开始和结束的时间戳
     *
     * @return array
     */
    public static function yesterday()
    {
        $yesterday = date('d') - 1;

        return [
            'start_time' => mktime(0, 0, 0, date('m'), $yesterday, date('Y')),
            'end_time' => mktime(23, 59, 59, date('m'), $yesterday, date('Y'))
        ];
    }
}
