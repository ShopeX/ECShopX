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

namespace CommunityBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * community_chief_apply_info 社区拼团团长申请表
 *
 * @ORM\Table(name="community_chief_apply_info", options={"comment"="社区拼团团长申请表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_chief_mobile", columns={"chief_mobile"}),
 *    @ORM\Index(name="ix_user_id", columns={"user_id"})
 * })
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityChiefApplyInfoRepository")
 */
class CommunityChiefApplyInfo
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="apply_id", type="bigint", options={"comment":"申请id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $apply_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id,为0时表示平台的团长申请", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员ID"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="chief_name", type="string", options={"comment":"团长名称"})
     */
    private $chief_name;

    /**
     * @var string
     *
     * @ORM\Column(name="chief_mobile", type="string", options={"comment":"团长手机号"})
     */
    private $chief_mobile;

    /**
     * @var text
     *
     * @ORM\Column(name="extra_data", type="text", options={"comment":"附加信息"})
     */
    private $extra_data;

    /**
     * @var integer
     *
     * @ORM\Column(name="approve_status", type="integer", options={"comment":"审批状态 0:未审批 1:同意 2:驳回", "default": 0})
     */
    private $approve_status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="refuse_reason", nullable=true, type="text", options={"comment":"拒绝原因"})
     */
    private $refuse_reason;

    /**
     * @var \DateTime $created_at
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created_at;

    /**
     * @var \DateTime $updated_at
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated_at;

    /**
     * get ApplyId
     *
     * @return int
     */
    public function getApplyId()
    {
        return $this->apply_id;
    }

    /**
     * get CompanyId
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * set CompanyId
     *
     * @param int $company_id
     *
     * @return self
     */
    public function setCompanyId($company_id)
    {
        $this->company_id = $company_id;
        return $this;
    }

    /**
     * get DistributorId
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * set DistributorId
     *
     * @param int $distributor_id
     *
     * @return self
     */
    public function setDistributorId($distributor_id)
    {
        $this->distributor_id = $distributor_id;
        return $this;
    }

    /**
     * get UserId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * set UserId
     *
     * @param int $user_id
     *
     * @return self
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * get ChiefName
     *
     * @return string
     */
    public function getChiefName()
    {
        return $this->chief_name;
    }

    /**
     * set ChiefName
     *
     * @param string $chief_name
     *
     * @return self
     */
    public function setChiefName($chief_name)
    {
        $this->chief_name = $chief_name;
        return $this;
    }

    /**
     * get ChiefMobile
     *
     * @return string
     */
    public function getChiefMobile()
    {
        return $this->chief_mobile;
    }

    /**
     * set ChiefMobile
     *
     * @param string $chief_mobile
     *
     * @return self
     */
    public function setChiefMobile($chief_mobile)
    {
        $this->chief_mobile = $chief_mobile;
        return $this;
    }

    /**
     * get ExtraData
     *
     * @return string
     */
    public function getExtraData()
    {
        return $this->extra_data ? json_decode($this->extra_data, true) : $this->extra_data;
    }

    /**
     * set ExtraData
     *
     * @param string $extra_data
     *
     * @return self
     */
    public function setExtraData($extra_data)
    {
        if (is_array($extra_data)) {
            $extra_data = json_encode($extra_data);
        }
        $this->extra_data = $extra_data;
        return $this;
    }

    /**
     * get ApproveStatus
     *
     * @return int
     */
    public function getApproveStatus()
    {
        return $this->approve_status;
    }

    /**
     * set ApproveStatus
     *
     * @param int $approve_status
     *
     * @return self
     */
    public function setApproveStatus($approve_status)
    {
        $this->approve_status = $approve_status;
        return $this;
    }

    /**
     * Set RefuseReason.
     *
     * @param string $refuse_reason
     *
     * @return self
     */
    public function setRefuseReason($refuse_reason)
    {
        $this->refuse_reason = $refuse_reason;

        return $this;
    }

    /**
     * Get RefuseReason.
     *
     * @return string
     */
    public function getRefuseReason()
    {
        return $this->refuse_reason;
    }

    /**
     * Set createdAt.
     *
     * @param int $createdAt
     *
     * @return CommunityChiefApplyInfo
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt.
     *
     * @param int|null $updatedAt
     *
     * @return CommunityChiefApplyInfo
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return int|null
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}
