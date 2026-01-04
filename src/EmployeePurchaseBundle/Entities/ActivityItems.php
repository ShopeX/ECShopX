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
 * ActivityItems 员工内购商品表
 *
 * @ORM\Table(name="employee_purchase_activity_items", options={"comment"="员工内购商品表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="idx_item_id", columns={"item_id"}),
 * })
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\ActivityItemsRepository")
 */
class ActivityItems
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
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品ID"})
     */
    private $item_id;

    /**
     * @var integer
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
     * @var integer
     *
     * @ORM\Column(name="activity_price", type="integer", options={"comment":"活动价,单位为‘分’"})
     */
    private $activity_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_store", type="integer", options={"comment":"活动库存数量"})
     */
    private $activity_store;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_fee", type="integer", options={"unsigned":true, "comment":"每人限额，以分为单位", "default":0})
     */
    private $limit_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_num", type="integer", options={"comment":"每人限购数量", "default":0})
     */
    private $limit_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", options={"comment":"排序", "default": 0})
     */
    private $sort = 0;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    /**
     * Set activityId.
     *
     * @param int $activityId
     *
     * @return ActivityItems
     */
    public function setActivityId($activityId)
    {
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
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return ActivityItems
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return ActivityItems
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
     * @return ActivityItems
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

    /**
     * Set activityPrice.
     *
     * @param int $activityPrice
     *
     * @return ActivityItems
     */
    public function setActivityPrice($activityPrice)
    {
        $this->activity_price = $activityPrice;

        return $this;
    }

    /**
     * Get activityPrice.
     *
     * @return int
     */
    public function getActivityPrice()
    {
        return $this->activity_price;
    }

    /**
     * Set activityStore.
     *
     * @param int $activityStore
     *
     * @return ActivityItems
     */
    public function setActivityStore($activityStore)
    {
        $this->activity_store = $activityStore;

        return $this;
    }

    /**
     * Get activityStore.
     *
     * @return int
     */
    public function getActivityStore()
    {
        return $this->activity_store;
    }

    /**
     * Set limitFee.
     *
     * @param int $limitFee
     *
     * @return ActivityItems
     */
    public function setLimitFee($limitFee)
    {
        $this->limit_fee = $limitFee;

        return $this;
    }

    /**
     * Get limitFee.
     *
     * @return int
     */
    public function getLimitFee()
    {
        return $this->limit_fee;
    }

    /**
     * Set limitNum.
     *
     * @param int $limitNum
     *
     * @return ActivityItems
     */
    public function setLimitNum($limitNum)
    {
        $this->limit_num = $limitNum;

        return $this;
    }

    /**
     * Get limitNum.
     *
     * @return int
     */
    public function getLimitNum()
    {
        return $this->limit_num;
    }

    /**
     * Set sort.
     *
     * @param int $sort
     *
     * @return ActivityItems
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ActivityItems
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int $updated
     *
     * @return ActivityItems
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
