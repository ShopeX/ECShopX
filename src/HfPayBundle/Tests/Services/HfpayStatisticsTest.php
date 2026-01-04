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

namespace HfPayBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use HfPayBundle\Services\HfpayCompanyDayStatisticsService;
use HfPayBundle\Services\HfpayDistributorStatisticsDayService;
use HfPayBundle\Services\HfpayDistributorTransactionStatisticsService;
use HfPayBundle\Services\HfpayStatisticsService;

class HfpayStatisticsTest extends TestBaseService
{
    /**
     * @var HfpayCompanyDayStatisticsService
     */
    protected $service;

    /**
     *  @var HfpayStatisticsService
     */
    protected $statisticsService;

    /**
     *  @var HfpayDistributorStatisticsDayService
     */
    protected $distributorService;

    protected $hfpayDistributorTransactionStatisticsService;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new HfpayCompanyDayStatisticsService();
        $this->distributorService = new HfpayDistributorStatisticsDayService();
        $this->statisticsService = new HfpayStatisticsService();
        $this->hfpayDistributorTransactionStatisticsService = new HfpayDistributorTransactionStatisticsService();
    }

    public function testStatistics()
    {
        $this->service->statistics();
    }

    public function testIncome()
    {
        $filter = [
            'company_id' => $this->getCompanyId(),
            'pay_type' => 'hfpay',
            'is_profitsharing' => 2,
            // 'pay_status' => 'PAYED',
            'trade_state' => 'SUCCESS',
            'time_expire|lte' => time(),
        ];

        $result = $this->service->income($filter);
        $this->assertEquals(2, count($result));
    }

    public function testRefund()
    {
        $filter = [
            'company_id' => $this->getCompanyId(),
            'pay_type' => 'hfpay',
            'is_profitsharing' => 2,
            // 'pay_status' => 'PAYED',
            'refund_status' => 'SUCCESS',
            'refund_success_time|lte' => time(),
        ];

        $result = $this->service->refund($filter);
        $this->assertEquals(2, count($result));
    }

    public function testOrderCount()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        // $filter['order_id'] = '213123';
        // $filter['distributor_id'] = '1';
        $filter['order_status'] = 'refunding';
        $this->service->orderCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderCount($this->getCompanyId(), $filter);
    }

    public function testOrderTotalFee()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        // $filter['order_id'] = '213123';
        // $filter['distributor_id'] = '1';
        $filter['order_status'] = 'refunding';
        $this->service->orderTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderTotalFee($this->getCompanyId(), $filter);
    }

    public function testorderRefundCount()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        // $filter['order_id'] = '213123';
        // $filter['distributor_id'] = '1';
        $filter['order_status'] = 'refunding';
        $this->service->orderRefundCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderRefundCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderRefundCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderRefundCount($this->getCompanyId(), $filter);
    }

    public function testOrderRefundTotalFee()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        // $filter['order_id'] = '213123';
        // $filter['distributor_id'] = '1';
        $filter['order_status'] = 'refunding';
        $this->service->orderRefundTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderRefundTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderRefundTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderRefundTotalFee($this->getCompanyId(), $filter);
    }

    public function testOrderRefundingCount()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        // $filter['order_id'] = '213123';
        // $filter['distributor_id'] = '1';
        $filter['order_status'] = 'refunding';
        $this->service->orderRefundingCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderRefundingCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderRefundingCount($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderRefundingCount($this->getCompanyId(), $filter);
    }
    public function testOrderRefundingTotalFee()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        // $filter['order_id'] = '213123';
        // $filter['distributor_id'] = '1';
        $filter['order_status'] = 'refunding';
        $this->service->orderRefundingTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderRefundingTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderRefundingTotalFee($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderRefundingTotalFee($this->getCompanyId(), $filter);
    }

    public function testOrderProfitSharingCharge()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        $filter['order_id'] = '3424465000130002';
        // $filter['distributor_id'] = '1';
        // $filter['order_status'] = 'refunding';
        $this->service->orderProfitSharingCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderProfitSharingCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderProfitSharingCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderProfitSharingCharge($this->getCompanyId(), $filter);
    }

    public function testOrderTotalCharge()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        // $filter['order_id'] = '3424465000130002';
        // $filter['distributor_id'] = '1';
        // $filter['order_status'] = 'refunding';
        $filter = [];
        $this->service->orderTotalCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderTotalCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderTotalCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderTotalCharge($this->getCompanyId(), $filter);
    }

    public function testorderRefundTotalCharge()
    {
        // $filter['create_time|gte'] = time();
        // $filter['create_time|lte'] = time();
        // $filter['profitsharing_status'] = 1;
        // $filter['order_id'] = '3424465000130002';
        // $filter['distributor_id'] = '1';
        // $filter['order_status'] = 'refunding';
        $filter = [];
        $this->service->orderRefundTotalCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'pay';
        $this->service->orderRefundTotalCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundsuccess';
        $this->service->orderRefundTotalCharge($this->getCompanyId(), $filter);

        $filter['order_status'] = 'refundfail';
        $this->service->orderRefundTotalCharge($this->getCompanyId(), $filter);
    }

    public function testCount()
    {
        $filter = [];
        $result = $this->statisticsService->count($this->getCompanyId(), $filter);
    }

    public function testGetOrderList()
    {
        $filter = [];
        $result = $this->statisticsService->getOrderList($this->getCompanyId(), $filter);
        $this->assertArrayHasKey('list', $result);
    }

    public function testGetOrderDetail()
    {
        $filter = [];
        $result = $this->statisticsService->getOrderDetail($this->getCompanyId(), '3426554000230013');
        var_dump($result);
    }

    public function testDistributorDay()
    {
        $this->distributorService->statistics();
    }

    public function testTransactionList()
    {
        $filter['company_id'] = $this->getCompanyId();
        $filter['start_date'] = '2021-06-01';
        $filter['end_date'] = '2021-06-31';
        $result = $this->hfpayDistributorTransactionStatisticsService->transactionList($filter);
    }
}
