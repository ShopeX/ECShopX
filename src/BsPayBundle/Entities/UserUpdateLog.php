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

namespace BsPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UserUpdateLog 用户进件修改log(审核成功后修改)
 *
 * @ORM\Table(name="bspay_user_update_log", options={"comment":"用户进件修改log(审核成功后修改)"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_sys_id", columns={"sys_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_huifu_id", columns={"huifu_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\UserUpdateLogRepository")
 */
class UserUpdateLog
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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sys_id", type="string", length=100, options={"comment":"sys_id", "default": ""})
     */
    private $sys_id;

    /**
     * @var string
     *
     * @ORM\Column(name="huifu_id", nullable=true, type="string", options={"comment":"汇付ID", "default": ""})
     */
    private $huifu_id;

    /**
     * @var string
     *
     * indv 个人
     * ent 企业
     *
     * @ORM\Column(name="user_type", type="string", length=20, options={"comment":"进件类型", "default": "indv"})
     */
    private $user_type;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", options={"comment":"用户表的主键id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", options={"comment":"修改数据 json"})
     */
    private $data;

    /**
     * @var string
     *
     * A 待审核
     * B 审核失败
     * C 开户失败
     * D 开户成功但未创建结算账户
     * E 开户和创建结算账户成功
     *
     * @ORM\Column(name="audit_state", nullable=true, type="string", length=50, options={"comment":"审核状态，状态包括：A-待审核；B-审核失败；C-开户失败；D-开户成功但未创建结算账户；E-开户和创建结算账户成功","default":"A"})
     */
    private $audit_state;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_desc", nullable=true, type="string", length=500, options={"comment":"审核结果描述","default":""})
     */
    private $audit_desc;

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
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
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
     * @param int|null $companyId
     *
     * @return UserUpdateLog
     */
    public function setCompanyId($companyId = null)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set sysId.
     *
     * @param string $sysId
     *
     * @return UserUpdateLog
     */
    public function setSysId($sysId)
    {
        $this->sys_id = $sysId;

        return $this;
    }

    /**
     * Get sysId.
     *
     * @return string
     */
    public function getSysId()
    {
        return $this->sys_id;
    }

    /**
     * Set huifuId.
     *
     * @param string|null $huifuId
     *
     * @return UserUpdateLog
     */
    public function setHuifuId($huifuId = null)
    {
        $this->huifu_id = $huifuId;

        return $this;
    }

    /**
     * Get huifuId.
     *
     * @return string|null
     */
    public function getHuifuId()
    {
        return $this->huifu_id;
    }

    /**
     * Set userType.
     *
     * @param string $userType
     *
     * @return UserUpdateLog
     */
    public function setUserType($userType)
    {
        $this->user_type = $userType;

        return $this;
    }

    /**
     * Get userType.
     *
     * @return string
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * Set userId.
     *
     * @param string $userId
     *
     * @return UserUpdateLog
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set data.
     *
     * @param string $data
     *
     * @return UserUpdateLog
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set auditState.
     *
     * @param string|null $auditState
     *
     * @return UserUpdateLog
     */
    public function setAuditState($auditState = null)
    {
        $this->audit_state = $auditState;

        return $this;
    }

    /**
     * Get auditState.
     *
     * @return string|null
     */
    public function getAuditState()
    {
        return $this->audit_state;
    }

    /**
     * Set auditDesc.
     *
     * @param string|null $auditDesc
     *
     * @return UserUpdateLog
     */
    public function setAuditDesc($auditDesc = null)
    {
        $this->audit_desc = $auditDesc;

        return $this;
    }

    /**
     * Get auditDesc.
     *
     * @return string|null
     */
    public function getAuditDesc()
    {
        return $this->audit_desc;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return UserUpdateLog
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
     * @return UserUpdateLog
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
