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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * DiscountCards(优惠券信息表)
 *
 * @ORM\Table(name="kaquan_discount_cards", options={"comment":"优惠券信息表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_source_id", columns={"source_id"})
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\DiscountCardsRepository")
 */
class DiscountCards
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="card_id", type="bigint", length=64, options={"comment":"卡券id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $card_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="card_type", type="string", length=16, options={"comment":"卡券类型，可选值有，discount:折扣券;cash:代金券;gift:兑换券;new_gift:兑换券(新)"})
     */
    private $card_type;

    /**
     * @var string
     *
     * @ORM\Column(name="brand_name", nullable=true, type="string", length=36, options={"comment":"商户名称"})
     */
    private $brand_name;

    /**
     * @var string
     *
     * @ORM\Column(name="logo_url", nullable=true, type="string", options={"comment":"卡券商户 logo"})
     */
    private $logo_url;

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
     * @var string
     *
     * @ORM\Column(name="notice", nullable=true, type="string", length=48, options={"comment":"卡券使用提醒,最大16汉字"})
     */
    private $notice;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", options={"comment":"卡券使用说明"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="date_type", type="string", options={"comment":"有效期的类型"})
     */
    private $date_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_date", nullable=true, type="integer", options={"comment":"有效期开始时间"})
     */
    private $begin_date;

    /**
     * @var datetime
     *
     * @ORM\Column(name="end_date", nullable=true, type="integer", options={"comment":"有效期结束时间"})
     */
    private $end_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="fixed_term", nullable=true, type="integer", options={"comment":"有效期的有效天数"})
     */
    private $fixed_term;

    /**
     * @var integer
     *
     * @ORM\Column(name="grade_ids", type="string", options={"comment":"指定会员id", "default": ""})
     */
    private $grade_ids = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="vip_grade_ids", type="string", options={"comment":"指定付费会员id", "default": ""})
     */
    private $vip_grade_ids = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="kq_status", type="integer", options={"comment":"卡券状态 0:正常 1:暂停 2:关闭", "default": 0})
     */
    private $kq_status = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="lock_time", type="integer", options={"comment":"兑换商品后的锁定时间", "default": 0})
     */
    private $lock_time = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="send_begin_time", nullable=true, type="integer", options={"comment":"发放开始时间"})
     */
    private $send_begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="send_end_time", nullable=true, type="integer", options={"comment":"发放结束时间"})
     */
    private $send_end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="service_phone", type="string", length=15, nullable=true, options={"comment":"客服电话"})
     */
    private $service_phone;

    /**
     * @var string
     *
     * @ORM\Column(name="center_title", type="string", nullable=true, options={"comment":"卡券顶部居中的按钮，仅在卡券状态正常(可以核销)时显示"})
     */
    private $center_title;

    /**
     * @var string
     *
     * @ORM\Column(name="center_sub_title", type="string", nullable=true, options={"comment":"显示在入口下方的提示语"})
     */
    private $center_sub_title;

    /**
     * @var string
     *
     * @ORM\Column(name="center_url", type="string", nullable=true, options={"comment":"顶部居中的url"})
     */
    private $center_url;

    /**
     * @var string
     *
     * @ORM\Column(name="custom_url_name", type="string", length=15, nullable=true, options={"comment":"自定义跳转外链的入口名字"})
     */
    private $custom_url_name;

    /**
     * @var string
     *
     * @ORM\Column(name="custom_url", type="string", nullable=true, options={"comment":"自定义跳转的URL"})
     */
    private $custom_url;

    /**
     * @var string
     *
     * @ORM\Column(name="custom_url_sub_title", type="string", length=18, nullable=true, options={"comment":"显示在入口右侧的提示语"})
     */
    private $custom_url_sub_title;

    /**
     * @var string
     *
     * @ORM\Column(name="promotion_url_name", type="string", length=15, nullable=true, options={"comment":"营销场景的自定义入口名称"})
     */
    private $promotion_url_name;

    /**
     * @var string
     *
     * @ORM\Column(name="promotion_url", type="string", nullable=true, options={"comment":"营销场景的自定义入口url"})
     */
    private $promotion_url;

    /**
     * @var string
     *
     * @ORM\Column(name="promotion_url_sub_title", type="string", length=18, nullable=true, options={"comment":"营销入口右侧的提示语"})
     */
    private $promotion_url_sub_title;

    /**
     * @var integer
     *
     * @ORM\Column(name="get_limit", type="integer", nullable=true, options={"comment":"每人可领券的数量限制"})
     */
    private $get_limit;

    /**
     * @var integer
     *
     * @ORM\Column(name="use_limit", type="integer", nullable=true, options={"comment":"每人可核销的数量限制"})
     */
    private $use_limit;

    /**
     * @var string
     *
     * @ORM\Column(name="can_share", type="string", nullable=true, options={"comment":"卡券领取页面是否可分享", "default":"false"})
     */
    private $can_share = "false";

    /**
     * @var string
     *
     * @ORM\Column(name="can_give_friend", type="string", nullable=true, options={"comment":"卡券是否可转赠", "default":"false"})
     */
    private $can_give_friend = "false";

    /**
     * @var string
     *
     * @ORM\Column(name="abstract", type="string", nullable=true, options={"comment":"封面摘要"})
     */
    private $abstract;

    /**
     * @var string
     *
     * @ORM\Column(name="icon_url_list", type="string", nullable=true, options={"comment":"封面图片"})
     */
    private $icon_url_list;

    /**
     * @var array
     *
     * @ORM\Column(name="text_image_list", type="array", nullable=true, options={"comment":"图文列表"})
     */
    private $text_image_list;

    /**
     * @var array
     *
     * @ORM\Column(name="time_limit", type="array", nullable=true, options={"comment":"使用时段限制"})
     */
    private $time_limit;

    /**
     * @var string
     *
     * @ORM\Column(name="gift", type="string", nullable=true, options={"comment":"兑换券兑换内容名称"})
     */
    private $gift;

    /**
     * @var string
     *
     * @ORM\Column(name="default_detail", type="string", nullable=true, options={"comment":"优惠券优惠详情"})
     */
    private $default_detail;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount", type="integer", nullable=true, options={"comment":"折扣券打折额度（百分比)","default" : 0})
     */
    private $discount = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="least_cost", type="integer", nullable=true, options={"comment":"代金券起用金额", "default":0})
     */
    private $least_cost = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="reduce_cost", type="integer", nullable=true, options={"comment":"代金券减免金额 or 兑换券起用金额","default":0})
     */
    private $reduce_cost = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="deal_detail", type="string", nullable=true, options={"comment":"团购券详情"})
     */
    private $deal_detail;

    /**
     * @var string
     *
     * @ORM\Column(name="accept_category", type="string", nullable=true, options={"comment":"指定可用的商品类目,代金券专用"})
     */
    private $accept_category;

    /**
     * @var string
     *
     * @ORM\Column(name="reject_category", type="string", nullable=true, options={"comment":"指定不可用的商品类目,代金券专用"})
     */
    private $reject_category;

    /**
     * @var string
     *
     * @ORM\Column(name="object_use_for", type="string", nullable=true, options={"comment":"购买xx可用类型门槛，仅用于兑换"})
     */
    private $object_use_for;

    /**
     * @var string
     *
     * @ORM\Column(name="can_use_with_other_discount", type="string", nullable=true, options={"comment":"是否可与其他优惠共享", "default":"false"})
     */
    private $can_use_with_other_discount = "false";

    /**
     * @var string
     * mall 商城专用
     * store  门店专用
     * @ORM\Column(name="use_platform", type="string", nullable=true, options={"comment":"优惠券适用平台（线上商城专用 or 门店专用）", "default":"store"})
     */
    private $use_platform = 'store';

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer", options={"comment":"卡券数量"})
     */
    private $quantity;

    /**
     * @var string
     *
     * @ORM\Column(name="use_all_shops", type="string", nullable=true, options={"comment":"是否适用所有门店", "default":"true"})
     */
    private $use_all_shops;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_shops_ids", type="text", nullable=true, options={"comment":"适用的门店"})
     */
    private $rel_shops_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="use_scenes", type="string", nullable=true, options={"comment":"核销场景。可选值有，ONLINE:线上商城(兑换券不可使用);QUICK:快捷买单(兑换券不可使用);SWEEP:门店支付(扫码核销);SELF:到店支付(自助核销)", "default":"QUICK"})
     */
    private $use_scenes = "QUICK";

    /**
     * @var integer
     *
     * @ORM\Column(name="self_consume_code", type="integer", nullable=true, options={"comment":"自助核销验证码","default":0})
     */
    private $self_consume_code = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="receive", type="string", nullable=true, options={"comment":"是否前台直接领取","default":"true"})
     */
    private $receive;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_id", type="text", nullable=true, options={"comment":"店铺id","default":","})
     */
    private $distributor_id;

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
     * @ORM\Column(type="integer")
     */
    protected $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="most_cost", type="integer", nullable=true, options={"comment":"代金券最高消费限额", "default":0})
     */
    private $most_cost = 99999900;

    /**
     * @var string
     *
     * @ORM\Column(name="use_bound", type="integer", options={"comment":"适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用", "default":0})
     */
    private $use_bound = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_ids", type="text", nullable=true, options={"comment":"标签id集合"})
     */
    private $tag_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="brand_ids", type="text", nullable=true, options={"comment":"品牌id集合"})
     */
    private $brand_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="apply_scope", type="text", nullable=true, options={"comment":"适用范围"})
     */
    private $apply_scope;

    /**
     * @var string
     *
     * @ORM\Column(name="card_code", type="string", nullable=true, options={"comment":"优惠券模板ID-第三方使用"})
     */
    private $card_code;

    /**
     * @var string
     *
     * @ORM\Column(name="card_rule_code", type="string", nullable=true, options={"comment":"优惠券规则ID-第三方使用"})
     */
    private $card_rule_code;

    /**
     * @var string
     *
     * @ORM\Column(name="source_type", type="string", length=20, nullable=true, options={"comment":"添加者类型：distributor"})
     */
    private $source_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", nullable=true, options={"comment":"添加者ID: 如店铺ID", "default":0})
     */
    private $source_id = 0;
    
    /**
     * @var string
     *
     * @ORM\Column(name="dm_card_id", type="string", nullable=true, options={"comment":"达摩CRM卡券ID"})
     */
    private $dm_card_id;
    
    /**
     * @var string
     * 多选
     * offlineStore 线下门店
     * ThirdmicroMall 第三方商城
     *
     * @ORM\Column(name="dm_use_channel", type="string", nullable=true, options={"comment":"达摩CRM适用渠道"})
     */
    private $dm_use_channel;

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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return DiscountCards
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
     * Set cardType
     *
     * @param string $cardType
     *
     * @return DiscountCards
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
     * Set brandName
     *
     * @param string $brandName
     *
     * @return DiscountCards
     */
    public function setBrandName($brandName)
    {
        $this->brand_name = $brandName;

        return $this;
    }

    /**
     * Get brandName
     *
     * @return string
     */
    public function getBrandName()
    {
        return $this->brand_name;
    }

    /**
     * Set logoUrl
     *
     * @param string $logoUrl
     *
     * @return DiscountCards
     */
    public function setLogoUrl($logoUrl)
    {
        $this->logo_url = $logoUrl;

        return $this;
    }

    /**
     * Get logoUrl
     *
     * @return string
     */
    public function getLogoUrl()
    {
        return $this->logo_url;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return DiscountCards
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
     * @return DiscountCards
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
     * Set notice
     *
     * @param string $notice
     *
     * @return DiscountCards
     */
    public function setNotice($notice)
    {
        $this->notice = $notice;

        return $this;
    }

    /**
     * Get notice
     *
     * @return string
     */
    public function getNotice()
    {
        return $this->notice;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return DiscountCards
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set dateType
     *
     * @param string $dateType
     *
     * @return DiscountCards
     */
    public function setDateType($dateType)
    {
        $this->date_type = $dateType;

        return $this;
    }

    /**
     * Get dateType
     *
     * @return string
     */
    public function getDateType()
    {
        return $this->date_type;
    }

    /**
     * Set beginDate
     *
     * @param integer $beginDate
     *
     * @return DiscountCards
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
     * @return DiscountCards
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
     * Set fixedTerm
     *
     * @param integer $fixedTerm
     *
     * @return DiscountCards
     */
    public function setFixedTerm($fixedTerm)
    {
        $this->fixed_term = $fixedTerm;

        return $this;
    }

    /**
     * Get fixedTerm
     *
     * @return integer
     */
    public function getFixedTerm()
    {
        return $this->fixed_term;
    }

    /**
     * Set servicePhone
     *
     * @param string $servicePhone
     *
     * @return DiscountCards
     */
    public function setServicePhone($servicePhone)
    {
        $this->service_phone = $servicePhone;

        return $this;
    }

    /**
     * Get servicePhone
     *
     * @return string
     */
    public function getServicePhone()
    {
        return $this->service_phone;
    }

    /**
     * Set centerTitle
     *
     * @param string $centerTitle
     *
     * @return DiscountCards
     */
    public function setCenterTitle($centerTitle)
    {
        $this->center_title = $centerTitle;

        return $this;
    }

    /**
     * Get centerTitle
     *
     * @return string
     */
    public function getCenterTitle()
    {
        return $this->center_title;
    }

    /**
     * Set centerSubTitle
     *
     * @param string $centerSubTitle
     *
     * @return DiscountCards
     */
    public function setCenterSubTitle($centerSubTitle)
    {
        $this->center_sub_title = $centerSubTitle;

        return $this;
    }

    /**
     * Get centerSubTitle
     *
     * @return string
     */
    public function getCenterSubTitle()
    {
        return $this->center_sub_title;
    }

    /**
     * Set centerUrl
     *
     * @param string $centerUrl
     *
     * @return DiscountCards
     */
    public function setCenterUrl($centerUrl)
    {
        $this->center_url = $centerUrl;

        return $this;
    }

    /**
     * Get centerUrl
     *
     * @return string
     */
    public function getCenterUrl()
    {
        return $this->center_url;
    }

    /**
     * Set customUrlName
     *
     * @param string $customUrlName
     *
     * @return DiscountCards
     */
    public function setCustomUrlName($customUrlName)
    {
        $this->custom_url_name = $customUrlName;

        return $this;
    }

    /**
     * Get customUrlName
     *
     * @return string
     */
    public function getCustomUrlName()
    {
        return $this->custom_url_name;
    }

    /**
     * Set customUrl
     *
     * @param string $customUrl
     *
     * @return DiscountCards
     */
    public function setCustomUrl($customUrl)
    {
        $this->custom_url = $customUrl;

        return $this;
    }

    /**
     * Get customUrl
     *
     * @return string
     */
    public function getCustomUrl()
    {
        return $this->custom_url;
    }

    /**
     * Set customUrlSubTitle
     *
     * @param string $customUrlSubTitle
     *
     * @return DiscountCards
     */
    public function setCustomUrlSubTitle($customUrlSubTitle)
    {
        $this->custom_url_sub_title = $customUrlSubTitle;

        return $this;
    }

    /**
     * Get customUrlSubTitle
     *
     * @return string
     */
    public function getCustomUrlSubTitle()
    {
        return $this->custom_url_sub_title;
    }

    /**
     * Set promotionUrlName
     *
     * @param string $promotionUrlName
     *
     * @return DiscountCards
     */
    public function setPromotionUrlName($promotionUrlName)
    {
        $this->promotion_url_name = $promotionUrlName;

        return $this;
    }

    /**
     * Get promotionUrlName
     *
     * @return string
     */
    public function getPromotionUrlName()
    {
        return $this->promotion_url_name;
    }

    /**
     * Set promotionUrl
     *
     * @param string $promotionUrl
     *
     * @return DiscountCards
     */
    public function setPromotionUrl($promotionUrl)
    {
        $this->promotion_url = $promotionUrl;

        return $this;
    }

    /**
     * Get promotionUrl
     *
     * @return string
     */
    public function getPromotionUrl()
    {
        return $this->promotion_url;
    }

    /**
     * Set promotionUrlSubTitle
     *
     * @param string $promotionUrlSubTitle
     *
     * @return DiscountCards
     */
    public function setPromotionUrlSubTitle($promotionUrlSubTitle)
    {
        $this->promotion_url_sub_title = $promotionUrlSubTitle;

        return $this;
    }

    /**
     * Get promotionUrlSubTitle
     *
     * @return string
     */
    public function getPromotionUrlSubTitle()
    {
        return $this->promotion_url_sub_title;
    }

    /**
     * Set getLimit
     *
     * @param integer $getLimit
     *
     * @return DiscountCards
     */
    public function setGetLimit($getLimit)
    {
        $this->get_limit = $getLimit;

        return $this;
    }

    /**
     * Get getLimit
     *
     * @return integer
     */
    public function getGetLimit()
    {
        return $this->get_limit;
    }

    /**
     * Set useLimit
     *
     * @param integer $useLimit
     *
     * @return DiscountCards
     */
    public function setUseLimit($useLimit)
    {
        $this->use_limit = $useLimit;

        return $this;
    }

    /**
     * Get useLimit
     *
     * @return integer
     */
    public function getUseLimit()
    {
        return $this->use_limit;
    }

    /**
     * Set canShare
     *
     * @param boolean $canShare
     *
     * @return DiscountCards
     */
    public function setCanShare($canShare)
    {
        $this->can_share = $canShare;

        return $this;
    }

    /**
     * Get canShare
     *
     * @return boolean
     */
    public function getCanShare()
    {
        return $this->can_share;
    }

    /**
     * Set canGiveFriend
     *
     * @param boolean $canGiveFriend
     *
     * @return DiscountCards
     */
    public function setCanGiveFriend($canGiveFriend)
    {
        $this->can_give_friend = $canGiveFriend;

        return $this;
    }

    /**
     * Get canGiveFriend
     *
     * @return boolean
     */
    public function getCanGiveFriend()
    {
        return $this->can_give_friend;
    }

    /**
     * Set abstract
     *
     * @param array $abstract
     *
     * @return DiscountCards
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;

        return $this;
    }

    /**
     * Get abstract
     *
     * @return array
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set textImageList
     *
     * @param array $textImageList
     *
     * @return DiscountCards
     */
    public function setTextImageList($textImageList)
    {
        $this->text_image_list = $textImageList;

        return $this;
    }

    /**
     * Get textImageList
     *
     * @return array
     */
    public function getTextImageList()
    {
        return $this->text_image_list;
    }

    /**
     * Set timeLimit
     *
     * @param array $timeLimit
     *
     * @return DiscountCards
     */
    public function setTimeLimit($timeLimit)
    {
        $this->time_limit = $timeLimit;

        return $this;
    }

    /**
     * Get timeLimit
     *
     * @return array
     */
    public function getTimeLimit()
    {
        return $this->time_limit;
    }

    /**
     * Set gift
     *
     * @param string $gift
     *
     * @return DiscountCards
     */
    public function setGift($gift)
    {
        $this->gift = $gift;

        return $this;
    }

    /**
     * Get gift
     *
     * @return string
     */
    public function getGift()
    {
        return $this->gift;
    }

    /**
     * Set defaultDetail
     *
     * @param string $defaultDetail
     *
     * @return DiscountCards
     */
    public function setDefaultDetail($defaultDetail)
    {
        $this->default_detail = $defaultDetail;

        return $this;
    }

    /**
     * Get defaultDetail
     *
     * @return string
     */
    public function getDefaultDetail()
    {
        return $this->default_detail;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     *
     * @return DiscountCards
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
     * @return DiscountCards
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
     * @return DiscountCards
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
     * Set dealDetail
     *
     * @param string $dealDetail
     *
     * @return DiscountCards
     */
    public function setDealDetail($dealDetail)
    {
        $this->deal_detail = $dealDetail;

        return $this;
    }

    /**
     * Get dealDetail
     *
     * @return string
     */
    public function getDealDetail()
    {
        return $this->deal_detail;
    }

    /**
     * Set acceptCategory
     *
     * @param string $acceptCategory
     *
     * @return DiscountCards
     */
    public function setAcceptCategory($acceptCategory)
    {
        $this->accept_category = $acceptCategory;

        return $this;
    }

    /**
     * Get acceptCategory
     *
     * @return string
     */
    public function getAcceptCategory()
    {
        return $this->accept_category;
    }

    /**
     * Set rejectCategory
     *
     * @param string $rejectCategory
     *
     * @return DiscountCards
     */
    public function setRejectCategory($rejectCategory)
    {
        $this->reject_category = $rejectCategory;

        return $this;
    }

    /**
     * Get rejectCategory
     *
     * @return string
     */
    public function getRejectCategory()
    {
        return $this->reject_category;
    }

    /**
     * Set objectUseFor
     *
     * @param string $objectUseFor
     *
     * @return DiscountCards
     */
    public function setObjectUseFor($objectUseFor)
    {
        $this->object_use_for = $objectUseFor;

        return $this;
    }

    /**
     * Get objectUseFor
     *
     * @return string
     */
    public function getObjectUseFor()
    {
        return $this->object_use_for;
    }

    /**
     * Set canUseWithOtherDiscount
     *
     * @param boolean $canUseWithOtherDiscount
     *
     * @return DiscountCards
     */
    public function setCanUseWithOtherDiscount($canUseWithOtherDiscount)
    {
        $this->can_use_with_other_discount = $canUseWithOtherDiscount;

        return $this;
    }

    /**
     * Get canUseWithOtherDiscount
     *
     * @return boolean
     */
    public function getCanUseWithOtherDiscount()
    {
        return $this->can_use_with_other_discount;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return DiscountCards
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set useAllShops
     *
     * @param boolean $useAllShops
     *
     * @return DiscountCards
     */
    public function setUseAllShops($useAllShops)
    {
        $this->use_all_shops = $useAllShops;

        return $this;
    }

    /**
     * Get useAllShops
     *
     * @return boolean
     */
    public function getUseAllShops()
    {
        return $this->use_all_shops;
    }

    /**
     * Set relShopsIds
     *
     * @param string $relShopsIds
     *
     * @return DiscountCards
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
     * Set created
     *
     * @param integer $created
     *
     * @return DiscountCards
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
     * @return DiscountCards
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
     * Set iconUrlList
     *
     * @param string $iconUrlList
     *
     * @return DiscountCards
     */
    public function setIconUrlList($iconUrlList)
    {
        $this->icon_url_list = $iconUrlList;

        return $this;
    }

    /**
     * Get iconUrlList
     *
     * @return string
     */
    public function getIconUrlList()
    {
        return $this->icon_url_list;
    }

    /**
     * Set useScenes
     *
     * @param string $useScenes
     *
     * @return DiscountCards
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
     * Set receive
     *
     * @param string $receive
     *
     * @return DiscountCards
     */
    public function setReceive($receive)
    {
        $this->receive = $receive;

        return $this;
    }

    /**
     * Get receive
     *
     * @return string
     */
    public function getReceive()
    {
        return $this->receive;
    }

    /**
     * Set selfConsumeCode
     *
     * @param integer $selfConsumeCode
     *
     * @return DiscountCards
     */
    public function setSelfConsumeCode($selfConsumeCode)
    {
        $this->self_consume_code = $selfConsumeCode;

        return $this;
    }

    /**
     * Get selfConsumeCode
     *
     * @return integer
     */
    public function getSelfConsumeCode()
    {
        return $this->self_consume_code;
    }

    /**
     * Set usePlatform
     *
     * @param string $usePlatform
     *
     * @return DiscountCards
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
     * Set mostCost
     *
     * @param integer $mostCost
     *
     * @return DiscountCards
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
     * Set distributorId
     *
     * @param string $distributorId
     *
     * @return DiscountCards
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return string
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set useBound.
     *
     * @param int $useBound
     *
     * @return DiscountCards
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
     * Set tagIds.
     *
     * @param string|null $tagIds
     *
     * @return DiscountCards
     */
    public function setTagIds($tagIds = null)
    {
        $this->tag_ids = $tagIds;

        return $this;
    }

    /**
     * Get tagIds.
     *
     * @return string|null
     */
    public function getTagIds()
    {
        return $this->tag_ids;
    }

    /**
     * Set brandIds.
     *
     * @param string|null $brandIds
     *
     * @return DiscountCards
     */
    public function setBrandIds($brandIds = null)
    {
        $this->brand_ids = $brandIds;

        return $this;
    }

    /**
     * Get brandIds.
     *
     * @return string|null
     */
    public function getBrandIds()
    {
        return $this->brand_ids;
    }

    /**
     * Set applyScope.
     *
     * @param string|null $applyScope
     *
     * @return DiscountCards
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
     * Set cardCode.
     *
     * @param string|null $cardCode
     *
     * @return DiscountCards
     */
    public function setCardCode($cardCode = null)
    {
        $this->card_code = $cardCode;

        return $this;
    }

    /**
     * Get cardCode.
     *
     * @return string|null
     */
    public function getCardCode()
    {
        return $this->card_code;
    }

    /**
     * Set cardRuleCode.
     *
     * @param string|null $cardRuleCode
     *
     * @return DiscountCards
     */
    public function setCardRuleCode($cardRuleCode = null)
    {
        $this->card_rule_code = $cardRuleCode;

        return $this;
    }

    /**
     * Get cardRuleCode.
     *
     * @return string|null
     */
    public function getCardRuleCode()
    {
        return $this->card_rule_code;
    }

    /**
     * Set sendBeginTime.
     *
     * @param int|null $sendBeginTime
     *
     * @return DiscountCards
     */
    public function setSendBeginTime($sendBeginTime = null)
    {
        $this->send_begin_time = $sendBeginTime;

        return $this;
    }

    /**
     * Get sendBeginTime.
     *
     * @return int|null
     */
    public function getSendBeginTime()
    {
        return $this->send_begin_time;
    }

    /**
     * Set sendEndTime.
     *
     * @param int|null $sendEndTime
     *
     * @return DiscountCards
     */
    public function setSendEndTime($sendEndTime = null)
    {
        $this->send_end_time = $sendEndTime;

        return $this;
    }

    /**
     * Get sendEndTime.
     *
     * @return int|null
     */
    public function getSendEndTime()
    {
        return $this->send_end_time;
    }

    /**
     * Set lockTime.
     *
     * @param int $lockTime
     *
     * @return DiscountCards
     */
    public function setLockTime($lockTime)
    {
        $this->lock_time = $lockTime;

        return $this;
    }

    /**
     * Get lockTime.
     *
     * @return int
     */
    public function getLockTime()
    {
        return $this->lock_time;
    }

    /**
     * Set kqStatus.
     *
     * @param int $kqStatus
     *
     * @return DiscountCards
     */
    public function setKqStatus($kqStatus)
    {
        $this->kq_status = $kqStatus;

        return $this;
    }

    /**
     * Get kqStatus.
     *
     * @return int
     */
    public function getKqStatus()
    {
        return $this->kq_status;
    }

    /**
     * Set gradeIds.
     *
     * @param string $gradeIds
     *
     * @return DiscountCards
     */
    public function setGradeIds($gradeIds)
    {
        $this->grade_ids = $gradeIds;

        return $this;
    }

    /**
     * Get gradeIds.
     *
     * @return string
     */
    public function getGradeIds()
    {
        return $this->grade_ids;
    }

    /**
     * Set vipGradeIds.
     *
     * @param string $vipGradeIds
     *
     * @return DiscountCards
     */
    public function setVipGradeIds($vipGradeIds)
    {
        $this->vip_grade_ids = $vipGradeIds;

        return $this;
    }

    /**
     * Get vipGradeIds.
     *
     * @return string
     */
    public function getVipGradeIds()
    {
        return $this->vip_grade_ids;
    }

    /**
     * Set sourceType.
     *
     * @param string|null $sourceType
     *
     * @return DiscountCards
     */
    public function setSourceType($sourceType = null)
    {
        $this->source_type = $sourceType;

        return $this;
    }

    /**
     * Get sourceType.
     *
     * @return string|null
     */
    public function getSourceType()
    {
        return $this->source_type;
    }

    /**
     * Set sourceId.
     *
     * @param int|null $sourceId
     *
     * @return DiscountCards
     */
    public function setSourceId($sourceId = null)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId.
     *
     * @return int|null
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set dmCardId.
     *
     * @param string|null $dmCardId
     *
     * @return DiscountCards
     */
    public function setDmCardId($dmCardId = null)
    {
        $this->dm_card_id = $dmCardId;

        return $this;
    }

    /**
     * Get dmCardId.
     *
     * @return string|null
     */
    public function getDmCardId()
    {
        return $this->dm_card_id;
    }

    /**
     * Set dmUseChannel.
     *
     * @param string|null $dmUseChannel
     *
     * @return DiscountCards
     */
    public function setDmUseChannel($dmUseChannel = null)
    {
        $this->dm_use_channel = $dmUseChannel;

        return $this;
    }

    /**
     * Get dmUseChannel.
     *
     * @return string|null
     */
    public function getDmUseChannel()
    {
        return $this->dm_use_channel;
    }
}
