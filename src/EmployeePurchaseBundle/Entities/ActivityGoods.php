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

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ActivityGoods 员工内购商品表
 *
 * @ORM\Table(name="employee_purchase_activity_goods", options={"comment"="员工内购商品表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="idx_goods_id", columns={"goods_id"}),
 * })
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\ActivityGoodsRepository")
 */
class ActivityGoods
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"商品ID"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * Set activityId.
     *
     * @param int $activityId
     *
     * @return ActivityGoods
     */
    public function setActivityId($activityId)
    {
        // ShopEx EcShopX Service Component
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId.
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return ActivityGoods
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ActivityGoods
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }
}
