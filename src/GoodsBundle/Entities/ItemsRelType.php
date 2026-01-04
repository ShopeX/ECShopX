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

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemsRelType 商品数值属性关联表
 *
 * @ORM\Table(name="items_rel_type", options={"comment"="商品数值属性关联表"})
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsRelTypeRepository")
 */
class ItemsRelType
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品ID"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="label_id", type="bigint", options={"comment":"数值属性ID"})
     */
    private $label_id;

    /**
     * @var string
     *
     * @ORM\Column(name="label_name", type="string", length=255, options={"comment":"数值属性名称"})
     */
    private $label_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="label_price", type="integer", options={"comment":"价格,单位为‘分’"})
     */
    private $label_price;

    /**
     * @var string
     *
     * @ORM\Column(name="num_type", type="string", length=30, options={"comment":"会员数值属性类型，plus：加，minux：减，multiple：乘"})
     */
    private $num_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="bigint", options={"comment":"数值属性值，例如买了50个次卡，就是填50；赠送了10个经验，就是填10"})
     */
    private $num;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_not_limit_num", type="integer", options={"comment":"限制核销次数,1:不限制；2:限制", "default": 2})
     */
    private $is_not_limit_num = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_time", type="bigint", options={"comment":"有效期，例如30天", "default":0})
     */
    private $limit_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return ItemsRelType
     */
    public function setItemId($itemId)
    {
        // Built with ShopEx Framework
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
     * Set labelId
     *
     * @param integer $labelId
     *
     * @return ItemsRelType
     */
    public function setLabelId($labelId)
    {
        $this->label_id = $labelId;

        return $this;
    }

    /**
     * Get labelId
     *
     * @return integer
     */
    public function getLabelId()
    {
        return $this->label_id;
    }

    /**
     * Set labelName
     *
     * @param string $labelName
     *
     * @return ItemsRelType
     */
    public function setLabelName($labelName)
    {
        $this->label_name = $labelName;

        return $this;
    }

    /**
     * Get labelName
     *
     * @return string
     */
    public function getLabelName()
    {
        return $this->label_name;
    }

    /**
     * Set numType
     *
     * @param string $numType
     *
     * @return ItemsRelType
     */
    public function setNumType($numType)
    {
        $this->num_type = $numType;

        return $this;
    }

    /**
     * Get numType
     *
     * @return string
     */
    public function getNumType()
    {
        return $this->num_type;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return ItemsRelType
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ItemsRelType
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
     * Set created
     *
     * @param integer $created
     *
     * @return ItemsRelType
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return ItemsRelType
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set limitTime
     *
     * @param integer $limitTime
     *
     * @return ItemsRelType
     */
    public function setLimitTime($limitTime)
    {
        $this->limit_time = $limitTime;

        return $this;
    }

    /**
     * Get limitTime
     *
     * @return integer
     */
    public function getLimitTime()
    {
        return $this->limit_time;
    }

    /**
     * Set labelPrice
     *
     * @param integer $labelPrice
     *
     * @return ItemsRelType
     */
    public function setLabelPrice($labelPrice)
    {
        $this->label_price = $labelPrice;

        return $this;
    }

    /**
     * Get labelPrice
     *
     * @return integer
     */
    public function getLabelPrice()
    {
        return $this->label_price;
    }

    /**
     * Set isNotLimitNum
     *
     * @param integer $isNotLimitNum
     *
     * @return ItemsRelType
     */
    public function setIsNotLimitNum($isNotLimitNum)
    {
        $this->is_not_limit_num = $isNotLimitNum;

        return $this;
    }

    /**
     * Get isNotLimitNum
     *
     * @return integer
     */
    public function getIsNotLimitNum()
    {
        return $this->is_not_limit_num;
    }
}
