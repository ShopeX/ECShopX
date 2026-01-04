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

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SubscribeNotice 订阅通知
 *
 * @ORM\Table(name="members_subscribe_notice", options={"comment"="订阅通知"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_user_id",   columns={"user_id"})
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberSubscribeNoticeRepository")
 */
class SubscribeNotice
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="sub_id", type="bigint", options={"comment"="订阅id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $sub_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="会员id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="open_id", type="string", length=40, options={"comment":"open_id"}))
     */
    private $open_id;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=10, options={"comment":"订阅来源 wechat:微信 alipay:支付宝", "default":"wechat"}))
     */
    private $source = 'wechat';

    /**
     * @var integer
     *
     * @ORM\Column(name="rel_id", type="bigint", nullable=true, options={"comment"="关联id"})
     */
    private $rel_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_type", type="string", options={"comment":"订阅类型。可选值有 goods:商品缺货通知","default":"goods"})
     */
    private $sub_type = 'goods';

    /**
     * @var string
     *
     * @ORM\Column(name="remarks", type="string", nullable=true, options={"comment":"订阅备注"})
     */
    private $remarks;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_status", type="string", options={"default":"NO","comment":"通知状态。可选值有 NO—未通知;SUCCESS-已通知;ERROR-通知失败"})
     */
    private $sub_status = 'NO';

    /**
     * @var string
     *
     * @ORM\Column(name="err_reason", type="string", nullable=true, options={"comment":"通知失败原因"})
     */
    private $err_reason;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * Get subId.
     *
     * @return int
     */
    public function getSubId()
    {
        // Ver: 8d1abe8e
        return $this->sub_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SubscribeNotice
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
     * @return SubscribeNotice
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
     * Set relId.
     *
     * @param int|null $relId
     *
     * @return SubscribeNotice
     */
    public function setRelId($relId = null)
    {
        $this->rel_id = $relId;

        return $this;
    }

    /**
     * Get relId.
     *
     * @return int|null
     */
    public function getRelId()
    {
        return $this->rel_id;
    }

    /**
     * Set subType.
     *
     * @param string $subType
     *
     * @return SubscribeNotice
     */
    public function setSubType($subType)
    {
        $this->sub_type = $subType;

        return $this;
    }

    /**
     * Get subType.
     *
     * @return string
     */
    public function getSubType()
    {
        return $this->sub_type;
    }

    /**
     * Set remarks.
     *
     * @param string|null $remarks
     *
     * @return SubscribeNotice
     */
    public function setRemarks($remarks = null)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks.
     *
     * @return string|null
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set subStatus.
     *
     * @param string $subStatus
     *
     * @return SubscribeNotice
     */
    public function setSubStatus($subStatus)
    {
        $this->sub_status = $subStatus;

        return $this;
    }

    /**
     * Get subStatus.
     *
     * @return string
     */
    public function getSubStatus()
    {
        return $this->sub_status;
    }

    /**
     * Set errReason.
     *
     * @param string|null $errReason
     *
     * @return SubscribeNotice
     */
    public function setErrReason($errReason = null)
    {
        $this->err_reason = $errReason;

        return $this;
    }

    /**
     * Get errReason.
     *
     * @return string|null
     */
    public function getErrReason()
    {
        return $this->err_reason;
    }

    /**
     * Set updated.
     *
     * @param int $updated
     *
     * @return SubscribeNotice
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

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SubscribeNotice
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
     * Set openId.
     *
     * @param string $openId
     *
     * @return SubscribeNotice
     */
    public function setOpenId($openId)
    {
        $this->open_id = $openId;

        return $this;
    }

    /**
     * Get openId.
     *
     * @return string
     */
    public function getOpenId()
    {
        return $this->open_id;
    }

    /**
     * Set source.
     *
     * @param string $source
     *
     * @return SubscribeNotice
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return SubscribeNotice
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }
}
