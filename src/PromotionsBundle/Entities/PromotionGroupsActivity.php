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
 * RegisterPromotions 拼团活动表
 *
 * @ORM\Table(name="promotion_groups_activity", options={"comment":"拼团活动表"}, indexes={
 *    @ORM\Index(name="idx_goodsid_begintime_endtime", columns={"goods_id","begin_time","end_time"})
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PromotionGroupsActivityRepository")
 */
class PromotionGroupsActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="groups_activity_id", type="bigint", options={"comment":"活动ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $groups_activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="act_name", type="string", length=50, options={"comment":"活动名称"})
     */
    private $act_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"商品ID"})
     */
    private $goods_id;

    /**
     * @var string
     *
     * @ORM\Column(name="group_goods_type", type="string", length=255, options={"comment":"团购活动商品类型", "default":"services"})
     */
    private $group_goods_type = 'services';

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="string", length=255, options={"comment":"活动封面"})
     */
    private $pics;

    /**
     * @var integer
     *
     * @ORM\Column(name="act_price", type="bigint", options={"comment":"活动价格"})
     */
    private $act_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="person_num", type="bigint", options={"comment":"拼团人数"})
     */
    private $person_num;

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
     * @var integer
     *
     * @ORM\Column(name="limit_buy_num", type="bigint", options={"comment":"限买数量"})
     */
    private $limit_buy_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_time", type="integer", options={"comment":"成团时效(单位时)"})
     */
    private $limit_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="store", type="bigint", options={"comment":"拼团库存"})
     */
    private $store;

    /**
     * @var boolean
     *
     * @ORM\Column(name="free_post", type="boolean", nullable=true, options={"comment":"是否包邮", "default": true})
     */
    private $free_post;

    /**
     * @var boolean
     *
     * @ORM\Column(name="rig_up", type="boolean", nullable=true, options={"comment":"是否展示开团列表", "default": true})
     */
    private $rig_up;

    /**
     * @var boolean
     *
     * @ORM\Column(name="robot", type="boolean", nullable=true, options={"comment":"成团机器人", "default": true})
     */
    private $robot;

    /**
     * @var string
     *
     * @ORM\Column(name="share_desc", type="string", nullable=true, length=100, options={"comment":"分享描述", "default": ""})
     */
    private $share_desc;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", nullable=true, options={"comment":"是否禁用 true=禁用,false=启用", "default": false})
     */
    private $disabled;

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
     * Get groupsActivityId
     *
     * @return integer
     */
    public function getGroupsActivityId()
    {
        return $this->groups_activity_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PromotionGroupsActivity
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
     * Set actName
     *
     * @param string $actName
     *
     * @return PromotionGroupsActivity
     */
    public function setActName($actName)
    {
        $this->act_name = $actName;

        return $this;
    }

    /**
     * Get actName
     *
     * @return string
     */
    public function getActName()
    {
        return $this->act_name;
    }

    /**
     * Set goodsId
     *
     * @param integer $goodsId
     *
     * @return PromotionGroupsActivity
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
     * Set actPrice
     *
     * @param integer $actPrice
     *
     * @return PromotionGroupsActivity
     */
    public function setActPrice($actPrice)
    {
        $this->act_price = $actPrice;

        return $this;
    }

    /**
     * Get actPrice
     *
     * @return integer
     */
    public function getActPrice()
    {
        return $this->act_price;
    }

    /**
     * Set personNum
     *
     * @param integer $personNum
     *
     * @return PromotionGroupsActivity
     */
    public function setPersonNum($personNum)
    {
        $this->person_num = $personNum;

        return $this;
    }

    /**
     * Get personNum
     *
     * @return integer
     */
    public function getPersonNum()
    {
        return $this->person_num;
    }

    /**
     * Set beginTime
     *
     * @param integer $beginTime
     *
     * @return PromotionGroupsActivity
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
     * @return PromotionGroupsActivity
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
     * Set limitBuyNum
     *
     * @param integer $limitBuyNum
     *
     * @return PromotionGroupsActivity
     */
    public function setLimitBuyNum($limitBuyNum)
    {
        $this->limit_buy_num = $limitBuyNum;

        return $this;
    }

    /**
     * Get limitBuyNum
     *
     * @return integer
     */
    public function getLimitBuyNum()
    {
        return $this->limit_buy_num;
    }

    /**
     * Set limitTime
     *
     * @param integer $limitTime
     *
     * @return PromotionGroupsActivity
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
     * Set freePost
     *
     * @param boolean $freePost
     *
     * @return PromotionGroupsActivity
     */
    public function setFreePost($freePost)
    {
        $this->free_post = $freePost;

        return $this;
    }

    /**
     * Get freePost
     *
     * @return boolean
     */
    public function getFreePost()
    {
        return $this->free_post;
    }

    /**
     * Set rigUp
     *
     * @param boolean $rigUp
     *
     * @return PromotionGroupsActivity
     */
    public function setRigUp($rigUp)
    {
        $this->rig_up = $rigUp;

        return $this;
    }

    /**
     * Get rigUp
     *
     * @return boolean
     */
    public function getRigUp()
    {
        return $this->rig_up;
    }

    /**
     * Set robot
     *
     * @param boolean $robot
     *
     * @return PromotionGroupsActivity
     */
    public function setRobot($robot)
    {
        $this->robot = $robot;

        return $this;
    }

    /**
     * Get robot
     *
     * @return boolean
     */
    public function getRobot()
    {
        return $this->robot;
    }

    /**
     * Set shareDesc
     *
     * @param string $shareDesc
     *
     * @return PromotionGroupsActivity
     */
    public function setShareDesc($shareDesc)
    {
        $this->share_desc = $shareDesc;

        return $this;
    }

    /**
     * Get shareDesc
     *
     * @return string
     */
    public function getShareDesc()
    {
        return $this->share_desc;
    }

    /**
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return PromotionGroupsActivity
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
     * Set created
     *
     * @param integer $created
     *
     * @return PromotionGroupsActivity
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
     * @return PromotionGroupsActivity
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
     * Set store
     *
     * @param integer $store
     *
     * @return PromotionGroupsActivity
     */
    public function setStore($store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get store
     *
     * @return integer
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set pics
     *
     * @param string $pics
     *
     * @return PromotionGroupsActivity
     */
    public function setPics($pics)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics
     *
     * @return string
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set groupGoodsType
     *
     * @param string $groupGoodsType
     *
     * @return PromotionGroupsActivity
     */
    public function setGroupGoodsType($groupGoodsType)
    {
        $this->group_goods_type = $groupGoodsType;

        return $this;
    }

    /**
     * Get groupGoodsType
     *
     * @return string
     */
    public function getGroupGoodsType()
    {
        return $this->group_goods_type;
    }
}
