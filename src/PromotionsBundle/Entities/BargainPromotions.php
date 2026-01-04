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
 * RegisterPromotions 砍价活动表
 *
 * @ORM\Table(name="promotions_bargain", options={"comment":"砍价活动表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\BargainPromotionsRepository")
 */
class BargainPromotions
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="bargain_id", type="bigint", options={"comment":"砍价活动ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $bargain_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", options={"comment":"活动名称"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_pic", type="string", options={"comment":"广告图"})
     */
    private $ad_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", length=255, options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="item_pics", type="string", options={"comment":"商品图片"})
     */
    private $item_pics;

    /**
     * @var string
     *
     * @ORM\Column(name="item_intro", type="text", nullable=true, options={"comment":"商品详情"})
     */
    private $item_intro;

    /**
     * @var integer
     *
     * @ORM\Column(name="mkt_price", type="integer", options={"comment":"市场价格,单位为‘分’"})
     */
    private $mkt_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"购买价格,单位为‘分’"})
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_num", type="integer", options={"comment":"购买限制"})
     */
    private $limit_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_num", type="integer", options={"comment":"已购买数量","default":0})
     */
    private $order_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="cutdown_rules", type="text", options={"comment":"砍价规则"})
     */
    private $bargain_rules;

    /**
     * @var string
     *
     * @ORM\Column(name="cutdown_range", type="json_array", options={"comment":"砍价范围，单位 分。值有 min 最小值;max 最大值"})
     */
    private $bargain_range;

    /**
     * @var string
     *
     * @ORM\Column(name="people_range", type="json_array", options={"comment":"砍价人数，单位(个)。值有 min 最小人数;max 最大人数"})
     */
    private $people_range;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_price", type="integer", options={"comment":"每个人最少能看的价钱,单位为‘分’"})
     */
    private $min_price = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_time", type="bigint", options={"comment":"开始时间"})
     */
    private $begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", options={"comment":"结束时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="share_msg", type="string", options={"comment":"分享内容"})
     */
    private $share_msg;

    /**
     * @var string
     *
     * @ORM\Column(name="help_pics", type="json_array", options={"comment":"翻牌图片"})
     */
    private $help_pics;

    /**
     * @var string
     *
     * @ORM\Column(name="item_id", type="string", length=11, options={"comment":"商品id"})
     */
    private $item_id;

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
     * Get bargainId
     *
     * @return integer
     */
    public function getBargainId()
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->bargain_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return BargainPromotions
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
     * Set title
     *
     * @param string $title
     *
     * @return BargainPromotions
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set adPic
     *
     * @param string $adPic
     *
     * @return BargainPromotions
     */
    public function setAdPic($adPic)
    {
        $this->ad_pic = $adPic;

        return $this;
    }

    /**
     * Get adPic
     *
     * @return string
     */
    public function getAdPic()
    {
        return $this->ad_pic;
    }

    /**
     * Set itemName
     *
     * @param string $itemName
     *
     * @return BargainPromotions
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
     * Set itemPics
     *
     * @param string $itemPics
     *
     * @return BargainPromotions
     */
    public function setItemPics($itemPics)
    {
        $this->item_pics = $itemPics;

        return $this;
    }

    /**
     * Get itemPics
     *
     * @return string
     */
    public function getItemPics()
    {
        return $this->item_pics;
    }

    /**
     * Set itemIntro
     *
     * @param string $itemIntro
     *
     * @return BargainPromotions
     */
    public function setItemIntro($itemIntro)
    {
        $this->item_intro = $itemIntro;

        return $this;
    }

    /**
     * Get itemIntro
     *
     * @return string
     */
    public function getItemIntro()
    {
        return $this->item_intro;
    }

    /**
     * Set mktPrice
     *
     * @param integer $mktPrice
     *
     * @return BargainPromotions
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
     * @return BargainPromotions
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
     * Set limitNum
     *
     * @param integer $limitNum
     *
     * @return BargainPromotions
     */
    public function setLimitNum($limitNum)
    {
        $this->limit_num = $limitNum;

        return $this;
    }

    /**
     * Get limitNum
     *
     * @return integer
     */
    public function getLimitNum()
    {
        return $this->limit_num;
    }

    /**
     * Set orderNum
     *
     * @param integer $orderNum
     *
     * @return BargainPromotions
     */
    public function setOrderNum($orderNum)
    {
        $this->order_num = $orderNum;

        return $this;
    }

    /**
     * Get orderNum
     *
     * @return integer
     */
    public function getOrderNum()
    {
        return $this->order_num;
    }

    /**
     * Set bargainRules
     *
     * @param string $bargainRules
     *
     * @return BargainPromotions
     */
    public function setBargainRules($bargainRules)
    {
        $this->bargain_rules = $bargainRules;

        return $this;
    }

    /**
     * Get bargainRules
     *
     * @return string
     */
    public function getBargainRules()
    {
        return $this->bargain_rules;
    }

    /**
     * Set bargainRange
     *
     * @param array $bargainRange
     *
     * @return BargainPromotions
     */
    public function setBargainRange($bargainRange)
    {
        $this->bargain_range = $bargainRange;

        return $this;
    }

    /**
     * Get bargainRange
     *
     * @return array
     */
    public function getBargainRange()
    {
        return $this->bargain_range;
    }

    /**
     * Set beginTime
     *
     * @param integer $beginTime
     *
     * @return BargainPromotions
     */
    public function setBeginTime($beginTime)
    {
        $this->begin_time = $beginTime;

        return $this;
    }

    /**
     * Get beginTime
     *
     * @return integer
     */
    public function getBeginTime()
    {
        return $this->begin_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return BargainPromotions
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set shareMsg
     *
     * @param string $shareMsg
     *
     * @return BargainPromotions
     */
    public function setShareMsg($shareMsg)
    {
        $this->share_msg = $shareMsg;

        return $this;
    }

    /**
     * Get shareMsg
     *
     * @return string
     */
    public function getShareMsg()
    {
        return $this->share_msg;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return BargainPromotions
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
     * @return BargainPromotions
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
     * Set helpPics
     *
     * @param array $helpPics
     *
     * @return BargainPromotions
     */
    public function setHelpPics($helpPics)
    {
        $this->help_pics = $helpPics;

        return $this;
    }

    /**
     * Get helpPics
     *
     * @return array
     */
    public function getHelpPics()
    {
        return $this->help_pics;
    }

    /**
     * Set minPrice
     *
     * @param integer $minPrice
     *
     * @return BargainPromotions
     */
    public function setMinPrice($minPrice)
    {
        $this->min_price = $minPrice;

        return $this;
    }

    /**
     * Get minPrice
     *
     * @return integer
     */
    public function getMinPrice()
    {
        return $this->min_price;
    }

    /**
     * Set peopleRange
     *
     * @param array $peopleRange
     *
     * @return BargainPromotions
     */
    public function setPeopleRange($peopleRange)
    {
        $this->people_range = $peopleRange;

        return $this;
    }

    /**
     * Get peopleRange
     *
     * @return array
     */
    public function getPeopleRange()
    {
        return $this->people_range;
    }

    /**
     * Set itemId.
     *
     * @param string $itemId
     *
     * @return BargainPromotions
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return string
     */
    public function getItemId()
    {
        return $this->item_id;
    }
}
