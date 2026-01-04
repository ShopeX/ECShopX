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

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDiscount 用户领取的优惠券表
 *
 * @ORM\Table(name="kaquan_user_discount", options={"comment":"用户领取的优惠券表"},indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_code", columns={"code"}),
 *    @ORM\Index(name="idx_source_type", columns={"source_type"}),
 *    @ORM\Index(name="idx_userid_status_companyid", columns={"user_id","status","company_id"}),
 *    @ORM\Index(name="idx_userid_enddate", columns={"user_id","end_date"}),
 *    @ORM\Index(name="idx_status_expiredtime", columns={"status", "expired_time"}),
 *    @ORM\Index(name="idx_cardid_companyid_userid", columns={"card_id", "company_id", "user_id"}),
 *    @ORM\Index(name="idx_dm_card_code", columns={"dm_card_code"}),
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\UserDiscountRepository")
 */
class UserDiscount
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", length=64, options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var bigint
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户的唯一标识"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="card_id", type="bigint", length=40, options={"comment":"微信用户领取的卡券 id ","defalult": 0})
     */
    private $card_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=30, options={"comment":"卡券 code 序列号"})
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="source_type", type="string", length=30, options={"comment":"卡券来源类型，可选值有 local:本地卡券,wechat:微信卡券","default":"local"})
     */
    private $source_type = "local";

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true, options={"comment":"用户领取的优惠券使用状态{1:未使用,2:已核销,3:已转赠,5:已过期,6:作废,10:已使用(兑换券)};","default":1})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="card_type", type="string", nullable=true, options={"comment":"优惠券类型,可选值有 discount:折扣券，cash:代金券，new_gift:兑换券","default":1})
     */
    private $card_type;

    /**
     * @var string
     *
     * @ORM\Column(name="use_platform", type="string", nullable=true, options={"comment":"优惠券适用平台（mall:线上商城专用, store:门店专用）","default":"store"})
     */
    private $use_platform = 'store';

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_date", type="integer", options={"comment":"有效期开始时间"})
     */
    private $begin_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_date", type="integer", options={"comment":"有效期结束时间"})
     */
    private $end_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="get_date", type="integer", options={"comment":"优惠券获取时间"})
     */
    private $get_date;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=27, options={"comment":"卡券名,最大9个汉字"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=16, options={"comment":"券颜色值"})
     */
    private $color;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount", type="integer", nullable=true, options={"comment":"折扣券打折额度（百分比)","default" : 0})
     */
    private $discount = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="least_cost", type="integer", nullable=true, options={"comment":"代金券起用金额","default" : 0})
     */
    private $least_cost = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="reduce_cost", type="integer", nullable=true, options={"comment":"代金券减免金额 or 兑换券起用金额","default" : 0})
     */
    private $reduce_cost = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_shops_ids", type="text", nullable=true, options={"comment":"门店shop_id;线下门店时,必填;"})
     */
    private $rel_shops_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_item_ids", type="text", nullable=true, options={"comment":"使用商品"})
     */
    private $rel_item_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_distributor_ids", type="text", nullable=true, options={"comment":"使用商品"})
     */
    private $rel_distributor_ids;

    /**
    * @var string
    *
    * @ORM\Column(name="consume_source", type="string", options={"comment":"核销来源"}, nullable=true)
    */
    private $consume_source;

    /**
    * @var string
    *
    * @ORM\Column(name="get_outer_str", type="string", options={"comment":"领取场景值"}, nullable=true)
    */
    private $get_outer_str;

    /**
    * @var string
    *
    * @ORM\Column(name="location_name", type="string", options={"comment":"核销卡券的门店名称"}, nullable=true)
    */
    private $location_name;

    /**
    * @var string
    *
    * @ORM\Column(name="staff_open_id", type="string", options={"comment":"卡券核销员"}, nullable=true)
    */
    private $staff_open_id;

    /**
    * @var string
    *
    * @ORM\Column(name="verify_code", type="string", options={"comment":"自助核销的验证码"}, nullable=true)
    */
    private $verify_code;

    /**
    * @var string
    *
    * @ORM\Column(name="remark_amount", type="string", options={"comment":"自助核销时备注金额"}, nullable=true)
    */
    private $remark_amount;

    /**
    * @var string
    *
    * @ORM\Column(name="consume_outer_str", type="string", options={"comment":"核销渠道"}, nullable=true)
    */
    private $consume_outer_str;

    /**
     * @var string
     *
     * @ORM\Column(name="trans_id", type="string", options={"comment":"微信支付交易订单号,买单核销专用"}, nullable=true)
     */
    private $trans_id;

    /**
     * @var string
     *
     * @ORM\Column(name="fee", type="string", options={"comment":"实付金额,买单核销专用"}, nullable=true)
     */
    private $fee;

    /**
     * @var string
     *
     * @ORM\Column(name="original_fee", type="string", options={"comment":"应付金额,买单核销专用"}, nullable=true)
     */
    private $original_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="location_id", type="string", options={"comment":"当前卡券核销的门店ID"}, nullable=true)
     */
    private $location_id;

    /**
     * @var string
     *
     * @ORM\Column(name="use_scenes", type="string", options={"comment":"可被核销的方式", "default":"QUICK"}, nullable=true)
     */
    private $use_scenes;

    /**
     * @var integer
     *
     * @ORM\Column(name="most_cost", type="integer", nullable=true, options={"comment":"代金券最高消费限额","default" : 0})
     */
    private $most_cost = 0;

    /**
     * @var array
     *     accept_category
     *     reject_category
     *     least_cost
     *     object_use_for
     *     can_use_with_other_discount
     *
     * @ORM\Column(name="use_condition", type="array", nullable=true, options={"comment":"使用条件字段"})
     */
    private $use_condition;

    /**
    * @var boolean
    *
    * @ORM\Column(name="is_give_by_friend", type="boolean", options={"comment":"是否为转赠领取","default":false}, nullable=true)
    */
    private $is_give_by_friend;

    /**
    * @var string
    *
    * @ORM\Column(name="old_code", type="string", options={"comment":"转赠之后,旧的 code 序列号"}, nullable=true)
    */
    private $old_code;

    /**
     * @var string
     *
     * @ORM\Column(name="friend_open_id", type="string", options={"comment":"转赠卡券时接收方 open_id"}, nullable=true)
     */
    private $friend_open_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_return_back", type="boolean", options={"comment":"转赠时是否退回", "default":false}, nullable=true)
     */
    private $is_return_back;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_chat_room", type="boolean", options={"comment":"是否群转赠", "default":false}, nullable=true)
     */
    private $is_chat_room;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", nullable=true, options={"comment":"导购id"})
     */
    private $salesperson_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="use_limited", type="integer", nullable=true, options={"comment":"是否可以多次使用,仅记录无业务","default" : 0})
     */
    private $use_limited = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="remain_times", type="integer", nullable=true, options={"comment":"剩余使用次数,仅记录无业务","default" : 1})
     */
    private $remain_times = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="use_bound", type="integer", options={"comment":"适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用", "default":0})
     */
    private $use_bound = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_category_ids", type="text", nullable=true, options={"comment":"可使用的类目"})
     */
    private $rel_category_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="apply_scope", type="text", nullable=true, options={"comment":"适用范围"})
     */
    private $apply_scope;

    /**
     * @var integer
     *
     * @ORM\Column(name="used_time", type="integer", options={"comment":"兑换券使用时间","default" : 0})
     */
    private $used_time = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="expired_time", type="integer", options={"comment":"兑换券过期时间","default" : 0})
     */
    private $expired_time = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_name", type="string", length=100, nullable=true, options={"comment":"参与活动名称"})
     */
    private $activity_name;

    /**
     * @var string
     *
     * @ORM\Column(name="dm_card_code", type="string", length=100, nullable=true, options={"comment":"达摩CRM会员卡券code"})
     */
    private $dm_card_code;

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
     * Set userId
     *
     * @param integer $userId
     *
     * @return UserDiscount
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UserDiscount
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
     * Set cardId
     *
     * @param integer $cardId
     *
     * @return UserDiscount
     */
    public function setCardId($cardId)
    {
        $this->card_id = $cardId;

        return $this;
    }

    /**
     * Get cardId
     *
     * @return integer
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return UserDiscount
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set sourceType
     *
     * @param string $sourceType
     *
     * @return UserDiscount
     */
    public function setSourceType($sourceType)
    {
        $this->source_type = $sourceType;

        return $this;
    }

    /**
     * Get sourceType
     *
     * @return string
     */
    public function getSourceType()
    {
        return $this->source_type;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return UserDiscount
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set cardType
     *
     * @param string $cardType
     *
     * @return UserDiscount
     */
    public function setCardType($cardType)
    {
        $this->card_type = $cardType;

        return $this;
    }

    /**
     * Get cardType
     *
     * @return string
     */
    public function getCardType()
    {
        return $this->card_type;
    }

    /**
     * Set usePlatform
     *
     * @param string $usePlatform
     *
     * @return UserDiscount
     */
    public function setUsePlatform($usePlatform)
    {
        $this->use_platform = $usePlatform;

        return $this;
    }

    /**
     * Get usePlatform
     *
     * @return string
     */
    public function getUsePlatform()
    {
        return $this->use_platform;
    }

    /**
     * Set beginDate
     *
     * @param integer $beginDate
     *
     * @return UserDiscount
     */
    public function setBeginDate($beginDate)
    {
        $this->begin_date = $beginDate;

        return $this;
    }

    /**
     * Get beginDate
     *
     * @return integer
     */
    public function getBeginDate()
    {
        return $this->begin_date;
    }

    /**
     * Set endDate
     *
     * @param integer $endDate
     *
     * @return UserDiscount
     */
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return integer
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return UserDiscount
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
     * Set color
     *
     * @param string $color
     *
     * @return UserDiscount
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     *
     * @return UserDiscount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return integer
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set leastCost
     *
     * @param integer $leastCost
     *
     * @return UserDiscount
     */
    public function setLeastCost($leastCost)
    {
        $this->least_cost = $leastCost;

        return $this;
    }

    /**
     * Get leastCost
     *
     * @return integer
     */
    public function getLeastCost()
    {
        return $this->least_cost;
    }

    /**
     * Set reduceCost
     *
     * @param integer $reduceCost
     *
     * @return UserDiscount
     */
    public function setReduceCost($reduceCost)
    {
        $this->reduce_cost = $reduceCost;

        return $this;
    }

    /**
     * Get reduceCost
     *
     * @return integer
     */
    public function getReduceCost()
    {
        return $this->reduce_cost;
    }

    /**
     * Set relShopsIds
     *
     * @param string $relShopsIds
     *
     * @return UserDiscount
     */
    public function setRelShopsIds($relShopsIds)
    {
        $this->rel_shops_ids = $relShopsIds;

        return $this;
    }

    /**
     * Get relShopsIds
     *
     * @return string
     */
    public function getRelShopsIds()
    {
        return $this->rel_shops_ids;
    }

    /**
     * Set relItemIds
     *
     * @param string $relItemIds
     *
     * @return UserDiscount
     */
    public function setRelItemIds($relItemIds)
    {
        $this->rel_item_ids = $relItemIds;

        return $this;
    }

    /**
     * Get relItemIds
     *
     * @return string
     */
    public function getRelItemIds()
    {
        return $this->rel_item_ids;
    }

    /**
     * Set relDistributorIds
     *
     * @param string $relDistributorIds
     *
     * @return UserDiscount
     */
    public function setRelDistributorIds($relDistributorIds)
    {
        $this->rel_distributor_ids = $relDistributorIds;

        return $this;
    }

    /**
     * Get relDistributorIds
     *
     * @return string
     */
    public function getRelDistributorIds()
    {
        return $this->rel_distributor_ids;
    }

    /**
     * Set consumeSource
     *
     * @param string $consumeSource
     *
     * @return UserDiscount
     */
    public function setConsumeSource($consumeSource)
    {
        $this->consume_source = $consumeSource;

        return $this;
    }

    /**
     * Get consumeSource
     *
     * @return string
     */
    public function getConsumeSource()
    {
        return $this->consume_source;
    }

    /**
     * Set getOuterStr
     *
     * @param string $getOuterStr
     *
     * @return UserDiscount
     */
    public function setGetOuterStr($getOuterStr)
    {
        $this->get_outer_str = $getOuterStr;

        return $this;
    }

    /**
     * Get getOuterStr
     *
     * @return string
     */
    public function getGetOuterStr()
    {
        return $this->get_outer_str;
    }

    /**
     * Set locationName
     *
     * @param string $locationName
     *
     * @return UserDiscount
     */
    public function setLocationName($locationName)
    {
        $this->location_name = $locationName;

        return $this;
    }

    /**
     * Get locationName
     *
     * @return string
     */
    public function getLocationName()
    {
        return $this->location_name;
    }

    /**
     * Set staffOpenId
     *
     * @param string $staffOpenId
     *
     * @return UserDiscount
     */
    public function setStaffOpenId($staffOpenId)
    {
        $this->staff_open_id = $staffOpenId;

        return $this;
    }

    /**
     * Get staffOpenId
     *
     * @return string
     */
    public function getStaffOpenId()
    {
        return $this->staff_open_id;
    }

    /**
     * Set verifyCode
     *
     * @param string $verifyCode
     *
     * @return UserDiscount
     */
    public function setVerifyCode($verifyCode)
    {
        $this->verify_code = $verifyCode;

        return $this;
    }

    /**
     * Get verifyCode
     *
     * @return string
     */
    public function getVerifyCode()
    {
        return $this->verify_code;
    }

    /**
     * Set remarkAmount
     *
     * @param string $remarkAmount
     *
     * @return UserDiscount
     */
    public function setRemarkAmount($remarkAmount)
    {
        $this->remark_amount = $remarkAmount;

        return $this;
    }

    /**
     * Get remarkAmount
     *
     * @return string
     */
    public function getRemarkAmount()
    {
        return $this->remark_amount;
    }

    /**
     * Set consumeOuterStr
     *
     * @param string $consumeOuterStr
     *
     * @return UserDiscount
     */
    public function setConsumeOuterStr($consumeOuterStr)
    {
        $this->consume_outer_str = $consumeOuterStr;

        return $this;
    }

    /**
     * Get consumeOuterStr
     *
     * @return string
     */
    public function getConsumeOuterStr()
    {
        return $this->consume_outer_str;
    }

    /**
     * Set transId
     *
     * @param string $transId
     *
     * @return UserDiscount
     */
    public function setTransId($transId)
    {
        $this->trans_id = $transId;

        return $this;
    }

    /**
     * Get transId
     *
     * @return string
     */
    public function getTransId()
    {
        return $this->trans_id;
    }

    /**
     * Set fee
     *
     * @param string $fee
     *
     * @return UserDiscount
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Get fee
     *
     * @return string
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set originalFee
     *
     * @param string $originalFee
     *
     * @return UserDiscount
     */
    public function setOriginalFee($originalFee)
    {
        $this->original_fee = $originalFee;

        return $this;
    }

    /**
     * Get originalFee
     *
     * @return string
     */
    public function getOriginalFee()
    {
        return $this->original_fee;
    }

    /**
     * Set locationId
     *
     * @param string $locationId
     *
     * @return UserDiscount
     */
    public function setLocationId($locationId)
    {
        $this->location_id = $locationId;

        return $this;
    }

    /**
     * Get locationId
     *
     * @return string
     */
    public function getLocationId()
    {
        return $this->location_id;
    }

    /**
     * Set useScenes
     *
     * @param string $useScenes
     *
     * @return UserDiscount
     */
    public function setUseScenes($useScenes)
    {
        $this->use_scenes = $useScenes;

        return $this;
    }

    /**
     * Get useScenes
     *
     * @return string
     */
    public function getUseScenes()
    {
        return $this->use_scenes;
    }

    /**
     * Set mostCost
     *
     * @param integer $mostCost
     *
     * @return UserDiscount
     */
    public function setMostCost($mostCost)
    {
        $this->most_cost = $mostCost;

        return $this;
    }

    /**
     * Get mostCost
     *
     * @return integer
     */
    public function getMostCost()
    {
        return $this->most_cost;
    }

    /**
     * Set useCondition
     *
     * @param array $useCondition
     *
     * @return UserDiscount
     */
    public function setUseCondition($useCondition)
    {
        $this->use_condition = $useCondition;

        return $this;
    }

    /**
     * Get useCondition
     *
     * @return array
     */
    public function getUseCondition()
    {
        return $this->use_condition;
    }

    /**
     * Set isGiveByFriend
     *
     * @param boolean $isGiveByFriend
     *
     * @return UserDiscount
     */
    public function setIsGiveByFriend($isGiveByFriend)
    {
        $this->is_give_by_friend = $isGiveByFriend;

        return $this;
    }

    /**
     * Get isGiveByFriend
     *
     * @return boolean
     */
    public function getIsGiveByFriend()
    {
        return $this->is_give_by_friend;
    }

    /**
     * Set oldCode
     *
     * @param string $oldCode
     *
     * @return UserDiscount
     */
    public function setOldCode($oldCode)
    {
        $this->old_code = $oldCode;

        return $this;
    }

    /**
     * Get oldCode
     *
     * @return string
     */
    public function getOldCode()
    {
        return $this->old_code;
    }

    /**
     * Set friendOpenId
     *
     * @param string $friendOpenId
     *
     * @return UserDiscount
     */
    public function setFriendOpenId($friendOpenId)
    {
        $this->friend_open_id = $friendOpenId;

        return $this;
    }

    /**
     * Get friendOpenId
     *
     * @return string
     */
    public function getFriendOpenId()
    {
        return $this->friend_open_id;
    }

    /**
     * Set isReturnBack
     *
     * @param boolean $isReturnBack
     *
     * @return UserDiscount
     */
    public function setIsReturnBack($isReturnBack)
    {
        $this->is_return_back = $isReturnBack;

        return $this;
    }

    /**
     * Get isReturnBack
     *
     * @return boolean
     */
    public function getIsReturnBack()
    {
        return $this->is_return_back;
    }

    /**
     * Set isChatRoom
     *
     * @param boolean $isChatRoom
     *
     * @return UserDiscount
     */
    public function setIsChatRoom($isChatRoom)
    {
        $this->is_chat_room = $isChatRoom;

        return $this;
    }

    /**
     * Get isChatRoom
     *
     * @return boolean
     */
    public function getIsChatRoom()
    {
        return $this->is_chat_room;
    }

    /**
     * Set getDate
     *
     * @param integer $getDate
     *
     * @return UserDiscount
     */
    public function setGetDate($getDate)
    {
        $this->get_date = $getDate;

        return $this;
    }

    /**
     * Get getDate
     *
     * @return integer
     */
    public function getGetDate()
    {
        return $this->get_date;
    }

    /**
     * Set salespersonId.
     *
     * @param int|null $salespersonId
     *
     * @return UserDiscount
     */
    public function setSalespersonId($salespersonId = null)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId.
     *
     * @return int|null
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set relCategoryIds.
     *
     * @param string|null $relCategoryIds
     *
     * @return UserDiscount
     */
    public function setRelCategoryIds($relCategoryIds = null)
    {
        $this->rel_category_ids = $relCategoryIds;

        return $this;
    }

    /**
     * Get relCategoryIds.
     *
     * @return string|null
     */
    public function getRelCategoryIds()
    {
        return $this->rel_category_ids;
    }

    /**
     * Set useLimited.
     *
     * @param int|null $useLimited
     *
     * @return UserDiscount
     */
    public function setUseLimited($useLimited = null)
    {
        $this->use_limited = $useLimited;

        return $this;
    }

    /**
     * Get useLimited.
     *
     * @return int|null
     */
    public function getUseLimited()
    {
        return $this->use_limited;
    }

    /**
     * Set remainTimes.
     *
     * @param int|null $remainTimes
     *
     * @return UserDiscount
     */
    public function setRemainTimes($remainTimes = null)
    {
        $this->remain_times = $remainTimes;

        return $this;
    }

    /**
     * Get remainTimes.
     *
     * @return int|null
     */
    public function getRemainTimes()
    {
        return $this->remain_times;
    }

    /**
     * Set useBound.
     *
     * @param int $useBound
     *
     * @return UserDiscount
     */
    public function setUseBound($useBound)
    {
        $this->use_bound = $useBound;

        return $this;
    }

    /**
     * Get useBound.
     *
     * @return int
     */
    public function getUseBound()
    {
        return $this->use_bound;
    }

    /**
     * Set applyScope.
     *
     * @param string|null $applyScope
     *
     * @return UserDiscount
     */
    public function setApplyScope($applyScope = null)
    {
        $this->apply_scope = $applyScope;

        return $this;
    }

    /**
     * Get applyScope.
     *
     * @return string|null
     */
    public function getApplyScope()
    {
        return $this->apply_scope;
    }

    /**
     * Set usedTime.
     *
     * @param int $usedTime
     *
     * @return UserDiscount
     */
    public function setUsedTime($usedTime)
    {
        $this->used_time = $usedTime;

        return $this;
    }

    /**
     * Get usedTime.
     *
     * @return int
     */
    public function getUsedTime()
    {
        return $this->used_time;
    }

    /**
     * Set expiredTime.
     *
     * @param int $expiredTime
     *
     * @return UserDiscount
     */
    public function setExpiredTime($expiredTime)
    {
        $this->expired_time = $expiredTime;

        return $this;
    }

    /**
     * Get expiredTime.
     *
     * @return int
     */
    public function getExpiredTime()
    {
        return $this->expired_time;
    }

    /**
     * Set activityName.
     *
     * @param string|null $activityName
     *
     * @return UserDiscount
     */
    public function setActivityName($activityName = null)
    {
        $this->activity_name = $activityName;

        return $this;
    }

    /**
     * Get activityName.
     *
     * @return string|null
     */
    public function getActivityName()
    {
        return $this->activity_name;
    }

    /**
     * Set dmCardCode.
     *
     * @param string|null $dmCardCode
     *
     * @return UserDiscount
     */
    public function setDmCardCode($dmCardCode = null)
    {
        $this->dm_card_code = $dmCardCode;

        return $this;
    }

    /**
     * Get dmCardCode.
     *
     * @return string|null
     */
    public function getDmCardCode()
    {
        return $this->dm_card_code;
    }
}
