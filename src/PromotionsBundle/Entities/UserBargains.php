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

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UserBargains 用户砍价表
 *
 * @ORM\Table(name="promotions_user_bargains", options={"comment":"用户砍价表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\UserBargainsRepository")
 */
class UserBargains
{
    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_appid", type="string", length=64, nullable=true, options={"comment":"公众号的appid"})
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", nullable=true, type="string", length=64, options={"comment":"公众号的appid"})
     */
    private $wxa_appid;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="bargain_id", type="bigint", options={"comment":"砍价ID"})
     */
    private $bargain_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", nullable=true, type="string", options={"comment":"订单标题"})
     */
    private $item_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="mkt_price", type="integer", options={"comment":"市场金额,单位为‘分’"})
     */
    private $mkt_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"购买金额,单位为‘分’"})
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="cutprice_num", type="integer", options={"comment":"砍价次数"})
     */
    private $cutprice_num;

    /**
     * @var string
     *
     * @ORM\Column(name="cutprice_range", type="json_array", options={"comment":"预先生成的砍价详情"})
     */
    private $cutprice_range;

    /**
     * @var integer
     *
     * @ORM\Column(name="cutdown_amount", type="integer", options={"comment":"已砍金额,单位为‘分’", "default": 0})
     */
    private $cutdown_amount = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_ordered", type="boolean", options={"comment":"是否已下单","default":false})
     */
    private $is_ordered = false;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated;

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UserBargains
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
     * Set authorizerAppid
     *
     * @param string $authorizerAppid
     *
     * @return UserBargains
     */
    public function setAuthorizerAppid($authorizerAppid)
    {
        $this->authorizer_appid = $authorizerAppid;

        return $this;
    }

    /**
     * Get authorizerAppid
     *
     * @return string
     */
    public function getAuthorizerAppid()
    {
        return $this->authorizer_appid;
    }

    /**
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return UserBargains
     */
    public function setWxaAppid($wxaAppid)
    {
        $this->wxa_appid = $wxaAppid;

        return $this;
    }

    /**
     * Get wxaAppid
     *
     * @return string
     */
    public function getWxaAppid()
    {
        return $this->wxa_appid;
    }

    /**
     * Set bargainId
     *
     * @param integer $bargainId
     *
     * @return UserBargains
     */
    public function setBargainId($bargainId)
    {
        $this->bargain_id = $bargainId;

        return $this;
    }

    /**
     * Get bargainId
     *
     * @return integer
     */
    public function getBargainId()
    {
        return $this->bargain_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return UserBargains
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set itemName
     *
     * @param string $itemName
     *
     * @return UserBargains
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set mktPrice
     *
     * @param integer $mktPrice
     *
     * @return UserBargains
     */
    public function setMktPrice($mktPrice)
    {
        $this->mkt_price = $mktPrice;

        return $this;
    }

    /**
     * Get mktPrice
     *
     * @return integer
     */
    public function getMktPrice()
    {
        return $this->mkt_price;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return UserBargains
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set cutdownAmount
     *
     * @param integer $cutdownAmount
     *
     * @return UserBargains
     */
    public function setCutdownAmount($cutdownAmount)
    {
        $this->cutdown_amount = $cutdownAmount;

        return $this;
    }

    /**
     * Get cutdownAmount
     *
     * @return integer
     */
    public function getCutdownAmount()
    {
        return $this->cutdown_amount;
    }

    /**
     * Set isOrdered
     *
     * @param boolean $isOrdered
     *
     * @return UserBargains
     */
    public function setIsOrdered($isOrdered)
    {
        $this->is_ordered = $isOrdered;

        return $this;
    }

    /**
     * Get isOrdered
     *
     * @return boolean
     */
    public function getIsOrdered()
    {
        return $this->is_ordered;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return UserBargains
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
     * @return UserBargains
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
     * Set cutpriceNum
     *
     * @param integer $cutpriceNum
     *
     * @return UserBargains
     */
    public function setCutpriceNum($cutpriceNum)
    {
        $this->cutprice_num = $cutpriceNum;

        return $this;
    }

    /**
     * Get cutpriceNum
     *
     * @return integer
     */
    public function getCutpriceNum()
    {
        return $this->cutprice_num;
    }

    /**
     * Set cutpriceRange
     *
     * @param array $cutpriceRange
     *
     * @return UserBargains
     */
    public function setCutpriceRange($cutpriceRange)
    {
        $this->cutprice_range = $cutpriceRange;

        return $this;
    }

    /**
     * Get cutpriceRange
     *
     * @return array
     */
    public function getCutpriceRange()
    {
        return $this->cutprice_range;
    }
}
