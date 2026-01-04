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
