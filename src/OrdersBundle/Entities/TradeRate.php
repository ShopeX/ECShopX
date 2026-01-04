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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * TradeRate 订单评价表
 *
 * @ORM\Table(name="trade_rate", options={"comment":"订单评价表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_goods_id", columns={"goods_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\TradeRateRepository")
 */
class TradeRate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="rate_id", type="bigint", options={"comment":"评价id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $rate_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;


    /**
     * @var integer
     *
     *
     * @ORM\Column(name="item_id", type="bigint",nullable=true,  options={"comment":"商品ID"})
     *
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", nullable=true, options={"comment":"产品ID", "default":0})
     */
    private $goods_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, nullable=true, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true,  options={"comment"="用户id"})
     *
     */
    private $user_id;



    /**
     * @var string
     *
     * @ORM\Column(name="rate_pic", type="text",  nullable=true, options={"comment":"评价图片"})
     */
    private $rate_pic;

    /**
     * @var integer
     *
     * @ORM\Column(name="rate_pic_num", type="integer", nullable=true, options={"comment":"评价图片数量","default":0})
     */
    private $rate_pic_num = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text",  nullable=true, options={"comment":"评价内容"})
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="content_len", type="integer", nullable=true, options={"comment":"评价内容长度","default":0})
     */
    private $content_len = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_reply", type="boolean", options={"comment":"评价是否回复。0:否；1:是", "default": 0})
     */
    private $is_reply = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"是否删除。0:否；1:是", "default": 0})
     */
    private $disabled = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="anonymous", type="boolean", options={"comment":"是否匿名。0:否；1:是", "default": 0})
     */
    private $anonymous = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="star", type="integer",length=1,  options={"comment":"评价星级", "default": 0})
     */
    private $star = 0;



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
     * @var string
     *
     * @ORM\Column(name="unionid", type="string", nullable=true, length=60, options={"comment"="微信unionid"})
     */
    private $unionid;

    /**
     * @var string
     *
     * @ORM\Column(name="item_spec_desc", type="text", nullable=true, options={"default":"","comment":"商品规格描述"})
     */
    private $item_spec_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", type="string", options={"comment":"订单类型。可选值有 normal:普通实体订单, pointsmall:积分商城订单","default":"normal"})
     */
    private $order_type = 'normal';

    /**
     * Get rateId
     *
     * @return integer
     */
    public function getRateId()
    {
        // Hash: 0d723eca
        return $this->rate_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return TradeRate
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return TradeRate
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
        // Hash: 0d723eca
        return $this->item_id;
    }

    /**
     * Set goodsId
     *
     * @param integer $goodsId
     *
     * @return TradeRate
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId
     *
     * @return integer
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set orderId
     *
     * @param string $orderId
     *
     * @return TradeRate
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return TradeRate
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
     * Set ratePic
     *
     * @param string $ratePic
     *
     * @return TradeRate
     */
    public function setRatePic($ratePic)
    {
        $this->rate_pic = $ratePic;

        return $this;
    }

    /**
     * Get ratePic
     *
     * @return string
     */
    public function getRatePic()
    {
        return $this->rate_pic;
    }

    /**
     * Set ratePicNum
     *
     * @param integer $ratePicNum
     *
     * @return TradeRate
     */
    public function setRatePicNum($ratePicNum)
    {
        $this->rate_pic_num = $ratePicNum;

        return $this;
    }

    /**
     * Get ratePicNum
     *
     * @return integer
     */
    public function getRatePicNum()
    {
        return $this->rate_pic_num;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return TradeRate
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set contentLen
     *
     * @param integer $contentLen
     *
     * @return TradeRate
     */
    public function setContentLen($contentLen)
    {
        $this->content_len = $contentLen;

        return $this;
    }

    /**
     * Get contentLen
     *
     * @return integer
     */
    public function getContentLen()
    {
        return $this->content_len;
    }

    /**
     * Set isReply
     *
     * @param boolean $isReply
     *
     * @return TradeRate
     */
    public function setIsReply($isReply)
    {
        $this->is_reply = $isReply;

        return $this;
    }

    /**
     * Get isReply
     *
     * @return boolean
     */
    public function getIsReply()
    {
        return $this->is_reply;
    }

    /**
     * Set star
     *
     * @param boolean $star
     *
     * @return TradeRate
     */
    public function setStar($star)
    {
        $this->star = $star;

        return $this;
    }

    /**
     * Get star
     *
     * @return boolean
     */
    public function getStar()
    {
        return $this->star;
    }


    /**
     * Set created
     *
     * @param integer $created
     *
     * @return TradeRate
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
     * @return TradeRate
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
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return TradeRate
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set anonymous
     *
     * @param boolean $anonymous
     *
     * @return TradeRate
     */
    public function setAnonymous($anonymous)
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    /**
     * Get anonymous
     *
     * @return boolean
     */
    public function getAnonymous()
    {
        return $this->anonymous;
    }

    /**
     * Set unionid
     *
     * @param string $unionid
     *
     * @return TradeRate
     */
    public function setUnionid($unionid)
    {
        $this->unionid = $unionid;

        return $this;
    }

    /**
     * Get unionid
     *
     * @return string
     */
    public function getUnionid()
    {
        return $this->unionid;
    }

    /**
     * Set itemSpecDesc
     *
     * @param string $itemSpecDesc
     *
     * @return TradeRate
     */
    public function setItemSpecDesc($itemSpecDesc)
    {
        $this->item_spec_desc = $itemSpecDesc;

        return $this;
    }

    /**
     * Get itemSpecDesc
     *
     * @return string
     */
    public function getItemSpecDesc()
    {
        return $this->item_spec_desc;
    }

    /**
     * Set orderType.
     *
     * @param string $orderType
     *
     * @return TradeRate
     */
    public function setOrderType($orderType)
    {
        $this->order_type = $orderType;

        return $this;
    }

    /**
     * Get orderType.
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->order_type;
    }
}
