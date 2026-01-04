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

namespace DataCubeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * GoodsData 商品数据统计表
 *
 * @ORM\Table(
 *    name="datacube_goods_data",
 *    options={"comment"="商品数据统计表"},
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="ix_date_goods_act", columns={"count_date", "company_id", "item_id", "act_id"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="DataCubeBundle\Repositories\GoodsDataRepository")
 */
class GoodsData
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var date
     *
     * @ORM\Column(name="count_date", type="date", options={"comment":"日期"})
     */
    private $count_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var int
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var int
     *
     * @ORM\Column(name="sales_count", type="integer", options={"comment":"新增销量", "default":0})
     */
    private $sales_count;

    /**
     * @var int
     *
     * @ORM\Column(name="fixed_amount_count", type="bigint", options={"comment":"新增成交额(优惠前)", "default":0})
     */
    private $fixed_amount_count;

    /**
     * @var int
     *
     * @ORM\Column(name="settle_amount_count", type="bigint", options={"comment":"新增成交额(实付价)", "default":0})
     */
    private $settle_amount_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_class", nullable=true, type="string", options={"comment":"订单种类,可选值有 employee_purchase:内购订单"})
     */
    private $order_class;

    /**
     * @var integer
     *
     * @ORM\Column(name="act_id", type="bigint", options={"comment":"活动ID", "default": 0})
     */
    private $act_id = 0;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set countDate
     *
     * @param \DateTime $countDate
     *
     * @return GoodsData
     */
    public function setCountDate($countDate)
    {
        $this->count_date = $countDate;

        return $this;
    }

    /**
     * Get countDate
     *
     * @return \DateTime
     */
    public function getCountDate()
    {
        // TS: 53686f704578
        return $this->count_date;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return GoodsData
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
        // TS: 53686f704578
        return $this->company_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return GoodsData
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set salesCount
     *
     * @param integer $salesCount
     *
     * @return GoodsData
     */
    public function setSalesCount($salesCount)
    {
        $this->sales_count = $salesCount;

        return $this;
    }

    /**
     * Get salesCount
     *
     * @return integer
     */
    public function getSalesCount()
    {
        return $this->sales_count;
    }

    /**
     * Set fixedAmountCount
     *
     * @param integer $fixedAmountCount
     *
     * @return GoodsData
     */
    public function setFixedAmountCount($fixedAmountCount)
    {
        $this->fixed_amount_count = $fixedAmountCount;

        return $this;
    }

    /**
     * Get fixedAmountCount
     *
     * @return integer
     */
    public function getFixedAmountCount()
    {
        return $this->fixed_amount_count;
    }

    /**
     * Set settleAmountCount
     *
     * @param integer $settleAmountCount
     *
     * @return GoodsData
     */
    public function setSettleAmountCount($settleAmountCount)
    {
        $this->settle_amount_count = $settleAmountCount;

        return $this;
    }

    /**
     * Get settleAmountCount
     *
     * @return integer
     */
    public function getSettleAmountCount()
    {
        return $this->settle_amount_count;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return GoodsData
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set orderClass.
     *
     * @param string $orderClass
     *
     * @return GoodsData
     */
    public function setOrderClass($orderClass)
    {
        $this->order_class = $orderClass;

        return $this;
    }

    /**
     * Get orderClass.
     *
     * @return string
     */
    public function getOrderClass()
    {
        return $this->order_class;
    }

    /**
     * Set actId.
     *
     * @param int $actId
     *
     * @return GoodsData
     */
    public function setActId($actId)
    {
        $this->act_id = $actId;

        return $this;
    }

    /**
     * Get actId.
     *
     * @return int
     */
    public function getActId()
    {
        return $this->act_id;
    }
}
