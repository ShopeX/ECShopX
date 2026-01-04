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

namespace PopularizeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Promoter 推广员表
 *
 * @ORM\Table(name="popularize_promoter", options={"comment":"推广员表"},indexes={
 *     @ORM\Index(name="idx_pid", columns={"pid"}),
 *     @ORM\Index(name="idx_companyid_userid", columns={"company_id","user_id"}),
 *     @ORM\Index(name="idx_identity_id", columns={"identity_id"}),
 *     @ORM\Index(name="idx_is_subordinates", columns={"is_subordinates"}),
 * })
 * @ORM\Entity(repositoryClass="PopularizeBundle\Repositories\PromoterRepository")
 */
class Promoter
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
     * @var string
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员ID"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_id", type="bigint", options={"comment":"推广员身份ID", "default":0})
     */
    private $identity_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_subordinates", type="integer", length=4, options={"comment":"是否可发展下级分销员", "default":0})
     */
    private $is_subordinates = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="pid", nullable=true, type="bigint", options={"comment":"上级会员ID"})
     */
    private $pid;

    /**
     * @var string
     *
     * @ORM\Column(name="pmobile", type="string", nullable=true, options={"comment":"上级手机号"})
     */
    private $pmobile;

    /**
     * @var string
     *
     * @ORM\Column(name="pname", nullable=true, type="string", options={"comment":"上级推广员名称"})
     */
    private $pname;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_name", nullable=true, type="string", options={"comment":"推广员自定义店铺名称"})
     */
    private $shop_name;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_name", nullable=true, type="string", options={"comment":"推广员提现的支付宝姓名"})
     */
    private $alipay_name;

    /**
     * @var string
     *
     * @ORM\Column(name="brief", nullable=true, type="string", options={"comment":"推广店铺描述"})
     */
    private $brief;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_pic", nullable=true, type="string", options={"comment":"推广店铺封面"})
     */
    private $shop_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_account", nullable=true, type="string", options={"comment":"推广员提现的支付宝账号"})
     */
    private $alipay_account;

    /**
     * @var integer
     *
     * @ORM\Column(name="grade_level", type="integer", length=4, options={"comment":"推广员等级"})
     */
    private $grade_level;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_promoter", type="integer", length=4, options={"comment":"是否为推广员"})
     */
    private $is_promoter;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_status", type="integer", length=4, options={"comment":"开店状态 0 未开店 1已开店 2申请中 3禁用 4申请审核拒绝 ", "default":0})
     */
    private $shop_status;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", nullable=true, type="string", options={"comment":"审核拒绝原因"})
     */
    private $reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="disabled", type="integer", options={"comment":"是否有效"})
     */
    private $disabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_buy", type="integer", options={"comment":"是否有购买记录"})
     */
    private $is_buy;

    /**
     * @var string
     *
     * @ORM\Column(name="promoter_name", nullable=true, type="string", options={"comment":"推广员名称"})
     */
    private $promoter_name;

    /**
     * @var string
     *
     * @ORM\Column(name="regions_id", nullable=true, type="text", options={"comment":"地区ID"})
     */
    private $regions_id;

    /**
     * @var string
     *
     * @ORM\Column(name="address", nullable=true, type="string", options={"comment":"地址"})
     */
    private $address;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Promoter
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return Promoter
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set identityId.
     *
     * @param int $identityId
     *
     * @return Promoter
     */
    public function setIdentityId($identityId)
    {
        $this->identity_id = $identityId;

        return $this;
    }

    /**
     * Get identityId.
     *
     * @return int
     */
    public function getIdentityId()
    {
        return $this->identity_id;
    }

    /**
     * Set isSubordinates.
     *
     * @param int $isSubordinates
     *
     * @return Promoter
     */
    public function setIsSubordinates($isSubordinates)
    {
        $this->is_subordinates = $isSubordinates;

        return $this;
    }

    /**
     * Get isSubordinates.
     *
     * @return int
     */
    public function getIsSubordinates()
    {
        return $this->is_subordinates;
    }

    /**
     * Set pid.
     *
     * @param int|null $pid
     *
     * @return Promoter
     */
    public function setPid($pid = null)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get pid.
     *
     * @return int|null
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set pmobile.
     *
     * @param string|null $pmobile
     *
     * @return Promoter
     */
    public function setPmobile($pmobile = null)
    {
        $this->pmobile = $pmobile;

        return $this;
    }

    /**
     * Get pmobile.
     *
     * @return string|null
     */
    public function getPmobile()
    {
        return $this->pmobile;
    }

    /**
     * Set pname.
     *
     * @param string|null $pname
     *
     * @return Promoter
     */
    public function setPname($pname = null)
    {
        $this->pname = $pname;

        return $this;
    }

    /**
     * Get pname.
     *
     * @return string|null
     */
    public function getPname()
    {
        return $this->pname;
    }

    /**
     * Set shopName.
     *
     * @param string|null $shopName
     *
     * @return Promoter
     */
    public function setShopName($shopName = null)
    {
        $this->shop_name = $shopName;

        return $this;
    }

    /**
     * Get shopName.
     *
     * @return string|null
     */
    public function getShopName()
    {
        return $this->shop_name;
    }

    /**
     * Set alipayName.
     *
     * @param string|null $alipayName
     *
     * @return Promoter
     */
    public function setAlipayName($alipayName = null)
    {
        $this->alipay_name = $alipayName;

        return $this;
    }

    /**
     * Get alipayName.
     *
     * @return string|null
     */
    public function getAlipayName()
    {
        return $this->alipay_name;
    }

    /**
     * Set brief.
     *
     * @param string|null $brief
     *
     * @return Promoter
     */
    public function setBrief($brief = null)
    {
        $this->brief = $brief;

        return $this;
    }

    /**
     * Get brief.
     *
     * @return string|null
     */
    public function getBrief()
    {
        return $this->brief;
    }

    /**
     * Set shopPic.
     *
     * @param string|null $shopPic
     *
     * @return Promoter
     */
    public function setShopPic($shopPic = null)
    {
        $this->shop_pic = $shopPic;

        return $this;
    }

    /**
     * Get shopPic.
     *
     * @return string|null
     */
    public function getShopPic()
    {
        return $this->shop_pic;
    }

    /**
     * Set alipayAccount.
     *
     * @param string|null $alipayAccount
     *
     * @return Promoter
     */
    public function setAlipayAccount($alipayAccount = null)
    {
        $this->alipay_account = $alipayAccount;

        return $this;
    }

    /**
     * Get alipayAccount.
     *
     * @return string|null
     */
    public function getAlipayAccount()
    {
        return $this->alipay_account;
    }

    /**
     * Set gradeLevel.
     *
     * @param int $gradeLevel
     *
     * @return Promoter
     */
    public function setGradeLevel($gradeLevel)
    {
        $this->grade_level = $gradeLevel;

        return $this;
    }

    /**
     * Get gradeLevel.
     *
     * @return int
     */
    public function getGradeLevel()
    {
        return $this->grade_level;
    }

    /**
     * Set isPromoter.
     *
     * @param int $isPromoter
     *
     * @return Promoter
     */
    public function setIsPromoter($isPromoter)
    {
        $this->is_promoter = $isPromoter;

        return $this;
    }

    /**
     * Get isPromoter.
     *
     * @return int
     */
    public function getIsPromoter()
    {
        return $this->is_promoter;
    }

    /**
     * Set shopStatus.
     *
     * @param int $shopStatus
     *
     * @return Promoter
     */
    public function setShopStatus($shopStatus)
    {
        $this->shop_status = $shopStatus;

        return $this;
    }

    /**
     * Get shopStatus.
     *
     * @return int
     */
    public function getShopStatus()
    {
        return $this->shop_status;
    }

    /**
     * Set reason.
     *
     * @param string|null $reason
     *
     * @return Promoter
     */
    public function setReason($reason = null)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason.
     *
     * @return string|null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set disabled.
     *
     * @param int $disabled
     *
     * @return Promoter
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return int
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set isBuy.
     *
     * @param int $isBuy
     *
     * @return Promoter
     */
    public function setIsBuy($isBuy)
    {
        $this->is_buy = $isBuy;

        return $this;
    }

    /**
     * Get isBuy.
     *
     * @return int
     */
    public function getIsBuy()
    {
        return $this->is_buy;
    }

    /**
     * Set promoterName.
     *
     * @param string|null $promoterName
     *
     * @return Promoter
     */
    public function setPromoterName($promoterName = null)
    {
        $this->promoter_name = $promoterName;

        return $this;
    }

    /**
     * Get promoterName.
     *
     * @return string|null
     */
    public function getPromoterName()
    {
        return $this->promoter_name;
    }

    /**
     * Set regionsId.
     *
     * @param string|null $regionsId
     *
     * @return Promoter
     */
    public function setRegionsId($regionsId = null)
    {
        $this->regions_id = $regionsId;

        return $this;
    }

    /**
     * Get regionsId.
     *
     * @return string|null
     */
    public function getRegionsId()
    {
        return $this->regions_id;
    }

    /**
     * Set address.
     *
     * @param string|null $address
     *
     * @return Promoter
     */
    public function setAddress($address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Promoter
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
     * @param int|null $updated
     *
     * @return Promoter
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
