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

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalespersonCouponStatistics;
use SalespersonBundle\Entities\ShopsRelSalesperson;

/**
 * 导购任务 class
 */
class SalespersonCouponStatisticsService
{
    public $salespersonCouponStatisticsRepository;

    public function __construct()
    {
        $this->salespersonCouponStatisticsRepository = app('registry')->getManager('default')->getRepository(SalespersonCouponStatistics::class);
    }

    /**
     * 导购赠送优惠券数量
     *
     * @param int $params 优惠券统计相关参数
     * @return void
     */
    public function completeCouponSend($params)
    {
        if ($this->checkParams($params)) {
            $filter = [
                'company_id' => $params['company_id'],
                'salesperson_id' => $params['salesperson_id'],
                'distributor_id' => $params['distributor_id'],
                'coupon_id' => $params['coupon_id'],
                'date' => date('Ymd'),
            ];
            $fieldNumber = [
                'send_num' => 1,
            ];
            $result = $this->salespersonCouponStatisticsRepository->add($fieldNumber, $filter);
            return $result;
        }
        return false;
    }

    /**
     * 导购分享优惠券支付
     *
     * @param int $params 优惠券统计相关参数
     * @return void
     */
    public function completeCouponPay($params)
    {
        if ($this->checkParams($params)) {
            $filter = [
                'company_id' => $params['company_id'],
                'salesperson_id' => $params['salesperson_id'],
                'distributor_id' => $params['distributor_id'],
                'coupon_id' => $params['coupon_id'],
                'date' => date('Ymd'),
            ];
            $fieldNumber = [
                'pay_num' => 1,
            ];
            $result = $this->salespersonCouponStatisticsRepository->add($fieldNumber, $filter);
            return $result;
        }
        return false;
    }

    /**
     * 导购分享优惠券取消支付
     *
     * @param int $params 优惠券统计相关参数
     * @return void
     */
    public function completeCouponRefund($params)
    {
        if ($this->checkParams($params)) {
            $filter = [
                'company_id' => $params['company_id'],
                'salesperson_id' => $params['salesperson_id'],
                'distributor_id' => $params['distributor_id'],
                'coupon_id' => $params['coupon_id'],
                'date' => date('Ymd'),
            ];
            $fieldNumber = [
                'pay_num' => -1,
            ];
            $result = $this->salespersonCouponStatisticsRepository->add($fieldNumber, $filter);
            return $result;
        }
        return false;
    }

    /**
     * 领取分享优惠券领取
     *
     * @param int $params 优惠券统计相关参数
     * @return void
     */
    public function completeCouponReceive($params)
    {
        // IDX: 2367340174
        if ($this->checkParams($params)) {
            $filter = [
                'company_id' => $params['company_id'],
                'salesperson_id' => $params['salesperson_id'],
                'distributor_id' => $params['distributor_id'],
                'coupon_id' => $params['coupon_id'],
                'date' => date('Ymd'),
            ];
            $fieldNumber = [
                'receive_num' => 1,
            ];
            $result = $this->salespersonCouponStatisticsRepository->add($fieldNumber, $filter);
            return $result;
        }
        return false;
    }

    /**
     * 通过分享优惠券注册
     *
     * @param int $params 优惠券统计相关参数
     * @return void
     */
    public function completeCouponReg($params)
    {
        if ($this->checkParams($params)) {
            $filter = [
                'company_id' => $params['company_id'],
                'salesperson_id' => $params['salesperson_id'],
                'distributor_id' => $params['distributor_id'],
                'coupon_id' => $params['coupon_id'],
                'date' => date('Ymd'),
            ];
            $fieldNumber = [
                'receive_num' => 1,
                'reg_num' => 1,
            ];
            $result = $this->salespersonCouponStatisticsRepository->add($fieldNumber, $filter);
            return $result;
        }
        return false;
    }

    /**
     * 检测并处理导购任务参数
     *
     * @param array $params
     * @return boolean
     */
    public function checkParams(array &$params)
    {
        $shopsRelSalespersonRepository = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
        $salespersonInfo = $shopsRelSalespersonRepository->getInfo(['salesperson_id' => $params['salesperson_id'], 'store_type' => 'distributor']);
        if (!$salespersonInfo) {
            return false;
        }
        $params['distributor_id'] = $salespersonInfo['shop_id'];
        return true;
    }


    /**
     * Dynamically call the SalespersonCouponStatisticsService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->salespersonCouponStatisticsRepository->$method(...$parameters);
    }
}
