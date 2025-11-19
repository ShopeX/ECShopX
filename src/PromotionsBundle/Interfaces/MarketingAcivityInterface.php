<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Interfaces;

interface MarketingAcivityInterface
{
    /**
     * 获取满折满减满赠促销规则描述，多条分号隔开
     */
    public function getFullProRules(string $filterType, array $rulesArr);

    /**
     *
     * @brief 应用满X件(Y折/Y元)
     *
     */

    public function applyActivityQuantity(array $activity, int $totalNum, int $totalFee);
    /**
     *
     * @brief  应用满X元(Y折/Y元)
     *
     */
    public function applyActivityTotalfee(array $activity, int $totalFee);
}
