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

namespace AdaPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AdapayMemberUpdateLog adapay子账户(member)修改log(审核成功后修改)
 *
 * @ORM\Table(name="adapay_member_update_log", options={"comment":"adapay子账户修改log(审核成功后修改)"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_app_id", columns={"app_id"}),
 *         @ORM\Index(name="idx_member_id", columns={"member_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayMemberUpdateLogRepository")
 */
class AdapayMemberUpdateLog
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
     * @ORM\Column(name="company_id", type="string", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", length=100, options={"comment":"应用app_id", "default": ""})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="member_id", type="string", options={"comment":"adapay_member的主键id(汇付id)"})
     */
    private $member_id;

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
     * @ORM\Column(name="audit_state", nullable=true, type="string", length=50, options={"comment":"审核状态，状态包括：A-待审核；B-审核失败；C-开户失败；D-开户成功但未创建结算账户；E-开户和创建结算账户成功","default":""})
     */
    private $audit_state;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_desc", nullable=true, type="string", length=500, options={"comment":"审核结果描述","default":""})
     */
    private $audit_desc;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;


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
     * @param string $companyId
     *
     * @return AdapayMemberUpdateLog
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set appId.
     *
     * @param string $appId
     *
     * @return AdapayMemberUpdateLog
     */
    public function setAppId($appId)
    {
        // ModuleID: 76fe2a3d
        $this->app_id = $appId;

        return $this;
    }

    /**
     * Get appId.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * Set memberId.
     *
     * @param string $memberId
     *
     * @return AdapayMemberUpdateLog
     */
    public function setMemberId($memberId)
    {
        $this->member_id = $memberId;

        return $this;
    }

    /**
     * Get memberId.
     *
     * @return string
     */
    public function getMemberId()
    {
        return $this->member_id;
    }

    /**
     * Set data.
     *
     * @param string $data
     *
     * @return AdapayMemberUpdateLog
     */
    public function setData($data)
    {
        $this->data = fixedencrypt($data);

        return $this;
    }

    /**
     * Get data.
     *
     * @return string
     */
    public function getData()
    {
        return fixeddecrypt($this->data);
    }

    /**
     * Set auditState.
     *
     * @param string|null $auditState
     *
     * @return AdapayMemberUpdateLog
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
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayMemberUpdateLog
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime.
     *
     * @return int
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime.
     *
     * @param int|null $updateTime
     *
     * @return AdapayMemberUpdateLog
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime.
     *
     * @return int|null
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set auditDesc.
     *
     * @param string|null $auditDesc
     *
     * @return AdapayMemberUpdateLog
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
}
