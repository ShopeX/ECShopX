<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Constants;

/**
 * 活动的日期范围 状态
 */
class DateStatusConstant
{
    /**
     * 未知
     */
    const UNKNOWN = 0;

    /**
     * 待开始
     */
    const COMING_SOON = 1;

    /**
     * 进行中
     */
    const ON_GOING = 2;

    /**
     * 已结束
     */
    const FINISHED = 3;
}