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

namespace ReservationBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * DefaultWorkShift(商户配置默认排班)
 *
 * @ORM\Table(name="reservation_default_work_shift", options={"comment":"商户配置默认排班"})
 * @ORM\Entity(repositoryClass="ReservationBundle\Repositories\DefaultWorkShiftRepository")
 */
class DefaultWorkShift
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"公司门店 id"})
     */
    private $shop_id;

    /**
     * @var array
     *
     * @ORM\Column(name="work_shift_data", type="array", options={"comment":"周一至周日每天的排班"})
     */
    private $work_shift_data;


    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return DefaultWorkShift
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return DefaultWorkShift
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set workShiftData
     *
     * @param array $workShiftData
     *
     * @return DefaultWorkShift
     */
    public function setWorkShiftData($workShiftData)
    {
        $this->work_shift_data = $workShiftData;

        return $this;
    }

    /**
     * Get workShiftData
     *
     * @return array
     */
    public function getWorkShiftData()
    {
        return $this->work_shift_data;
    }
}
