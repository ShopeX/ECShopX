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

namespace PromotionsBundle\Services;

// 优惠券发放错误日志记录
use PromotionsBundle\Entities\CouponGiveLog;

class CouponGiveLogService
{
    public $pageSize = 50;

    public $couponGiveLogRepository;

    public function __construct()
    {
        $this->couponGiveLogRepository = app('registry')->getManager('default')->getRepository(CouponGiveLog::class);
    }

    /**
     * 创建错误日志记录
     * @param $params
     */
    public function createCouponGiveLog($data)
    {
        return $this->couponGiveLogRepository->create($data);
    }

    public function updateCouponGiveLog($filter, $data)
    {
        return $this->couponGiveLogRepository->updateOneBy($filter, $data);
    }

    /**
     * 获取模版订单列表
     */
    public function getCouponGiveLogList(array $filter, $page = 1, $pageSize = 100, $orderBy = ['created' => 'DESC'])
    {
        return $this->couponGiveLogRepository->lists($filter, $orderBy, $pageSize, $page);
    }
}
