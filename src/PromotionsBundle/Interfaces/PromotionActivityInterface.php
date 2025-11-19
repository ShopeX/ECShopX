<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Interfaces;

interface PromotionActivityInterface
{
    /**
     * 添加活动判断特有参数
     */
    public function checkActivityParams(array $data);

    /**
     * 是否触发活动
     */
    public function getSourceFromStr();
}
