<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
