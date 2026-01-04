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
 * AdapayMember 实名用户对象
 *
 * @ORM\Table(name="adapay_member", options={"comment":"实名用户对象"},
 *     indexes={
 *         @ORM\Index(name="idx_tel_no", columns={"tel_no"}, options={"lengths": {64}}),
 *         @ORM\Index(name="idx_user_name", columns={"user_name"}, options={"lengths": {64}}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_cert_id", columns={"cert_id"}, options={"lengths": {64}}),
 *         @ORM\Index(name="idx_pid", columns={"pid"}),
 *         @ORM\Index(name="idx_operator_id", columns={"operator_id"}),
 *         @ORM\Index(name="idx_audit_state", columns={"audit_state"})
 *     }),
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayMemberRepository")
 */
class AdapayMember
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
     * @ORM\Column(name="app_id", type="string", length=100, options={"comment":"应用app_id", "default": ""})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", nullable=true, length=200, options={"comment":"用户地址", "default": ""})
     */
    private $location;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="pid", type="bigint", options={"comment":"父ID", "default": 0})
     */
    private $pid = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_update", type="integer", length=10, options={"comment":"是否审核成功后修改 1:是  0:否", "default": 0})
     */
    private $is_update = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", nullable=true, type="integer", options={"comment":"操作者id", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_type", type="string", options={"comment":"操作者类型:distributor-店铺;dealer-经销;promoter-推广员"})
     */
    private $operator_type;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true, length=100, options={"comment":"用户邮箱", "default": ""})
     */
    private $email;

    /**
     * @var string
     *
     * person 个人
     * corp 企业
     *
     * @ORM\Column(name="member_type", nullable=true, type="string", length=20, options={"comment":"账户类型", "default": "person"})
     */
    private $member_type;

    /**
     * @var string
     *
     * MALE 男
     * FEMALE 女
     *
     * @ORM\Column(name="gender", nullable=true, type="string", length=50, options={"comment":"性别，为空时表示未填写", "default": ""})
     */
    private $gender;

    /**
     * @var string
     *
     * @ORM\Column(name="nickname", nullable=true, type="string", length=50, options={"comment":"用户昵称", "default": ""})
     */
    private $nickname;

    /**
     * @var string
     *
     * @ORM\Column(name="tel_no", nullable=true, type="string", length=255, options={"comment":"用户手机号","default":""})
     */
    private $tel_no;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", nullable=true, type="string", length=500, options={"comment":"用户姓名","default":""})
     */
    private $user_name;

    /**
     * @var string
     *
     * 00 身份证
     *
     * @ORM\Column(name="cert_type", nullable=true, type="string", length=10, options={"comment":"证件类型，仅支持：00-身份证","default":"00"})
     */
    private $cert_type;

    /**
     * @var string
     *
     * @ORM\Column(name="cert_id", nullable=true, type="string", length=255, options={"comment":"证件号","default":""})
     */
    private $cert_id;

    /**
     * @var string
     *
     * @ORM\Column(name="is_sms", type="string", length=20, nullable=true, options={"comment":"是否短信提醒: 1:是  0:否"})
     */
    private $is_sms;

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
     * @var string
     *
     * pending 交易处理中
     * succeeded 交易成功
     * failed 交易失败
     *
     * @ORM\Column(name="status", nullable=true, type="string", length=50, options={"comment":"当前交易状态","default":""})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_info", nullable=true, type="string", length=500, options={"comment":"错误描述","default":""})
     */
    private $error_info;

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
     * @var string
     *
     * @ORM\Column(name="valid", type="boolean", nullable=true, options={"default":0, "comment":"是否点过结算中心"})
     */
    private $valid = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_created", type="boolean", options={"comment":"会员是否创建成功", "default": 0})
     */
    private $is_created = 0;

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
     * Set appId.
     *
     * @param string $appId
     *
     * @return AdapayMember
     */
    public function setAppId($appId)
    {
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
     * Set location.
     *
     * @param string $location
     *
     * @return AdapayMember
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AdapayMember
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
     * Set pid.
     *
     * @param int $pid
     *
     * @return AdapayMember
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get pid.
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return AdapayMember
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set memberType.
     *
     * @param string|null $memberType
     *
     * @return AdapayMember
     */
    public function setMemberType($memberType = null)
    {
        $this->member_type = $memberType;

        return $this;
    }

    /**
     * Get memberType.
     *
     * @return string|null
     */
    public function getMemberType()
    {
        return $this->member_type;
    }

    /**
     * Set gender.
     *
     * @param string|null $gender
     *
     * @return AdapayMember
     */
    public function setGender($gender = null)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender.
     *
     * @return string|null
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set nickname.
     *
     * @param string $nickname
     *
     * @return AdapayMember
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Get nickname.
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Set telNo.
     *
     * @param string $telNo
     *
     * @return AdapayMember
     */
    public function setTelNo($telNo)
    {
        $this->tel_no = fixedencrypt($telNo);

        return $this;
    }

    /**
     * Get telNo.
     *
     * @return string
     */
    public function getTelNo()
    {
        return fixeddecrypt($this->tel_no);
    }

    /**
     * Set userName.
     *
     * @param string $userName
     *
     * @return AdapayMember
     */
    public function setUserName($userName)
    {
        $this->user_name = fixedencrypt($userName);

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string
     */
    public function getUserName()
    {
        return fixeddecrypt($this->user_name);
    }

    /**
     * Set certType.
     *
     * @param string $certType
     *
     * @return AdapayMember
     */
    public function setCertType($certType)
    {
        $this->cert_type = $certType;

        return $this;
    }

    /**
     * Get certType.
     *
     * @return string
     */
    public function getCertType()
    {
        return $this->cert_type;
    }

    /**
     * Set certId.
     *
     * @param string $certId
     *
     * @return AdapayMember
     */
    public function setCertId($certId)
    {
        $this->cert_id = fixedencrypt($certId);

        return $this;
    }

    /**
     * Get certId.
     *
     * @return string
     */
    public function getCertId()
    {
        return fixeddecrypt($this->cert_id);
    }

    /**
     * Set auditState.
     *
     * @param string $auditState
     *
     * @return AdapayMember
     */
    public function setAuditState($auditState)
    {
        $this->audit_state = $auditState;

        return $this;
    }

    /**
     * Get auditState.
     *
     * @return string
     */
    public function getAuditState()
    {
        return $this->audit_state;
    }

    /**
     * Set auditDesc.
     *
     * @param string $auditDesc
     *
     * @return AdapayMember
     */
    public function setAuditDesc($auditDesc)
    {
        $this->audit_desc = $auditDesc;

        return $this;
    }

    /**
     * Get auditDesc.
     *
     * @return string
     */
    public function getAuditDesc()
    {
        return $this->audit_desc;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return AdapayMember
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set errorInfo.
     *
     * @param string $errorInfo
     *
     * @return AdapayMember
     */
    public function setErrorInfo($errorInfo)
    {
        $this->error_info = $errorInfo;

        return $this;
    }

    /**
     * Get errorInfo.
     *
     * @return string
     */
    public function getErrorInfo()
    {
        return $this->error_info;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayMember
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
     * @return AdapayMember
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
     * Set operatorId.
     *
     * @param int|null $operatorId
     *
     * @return AdapayMember
     */
    public function setOperatorId($operatorId = null)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int|null
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set operatorType.
     *
     * @param int|null $operatorType
     *
     * @return AdapayMember
     */
    public function setOperatorType($operatorType = null)
    {
        $this->operator_type = $operatorType;

        return $this;
    }

    /**
     * Get operatorType.
     *
     * @return int|null
     */
    public function getOperatorType()
    {
        return $this->operator_type;
    }

    /**
     * Set valid.
     *
     * @param int|null $valid
     *
     * @return AdapayMember
     */
    public function setValid($valid = null)
    {
        $this->valid = $valid;
        return $this;
    }

    /**
     * Get checked.
     *
     * @return int|null
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * Set isSms.
     *
     * @param string|null $isSms
     *
     * @return AdapayMember
     */
    public function setIsSms($isSms = null)
    {
        $this->is_sms = $isSms;

        return $this;
    }

    /**
     * Get isSms.
     *
     * @return string|null
     */
    public function getIsSms()
    {
        return $this->is_sms;
    }

    /**
     * Set isUpdate.
     *
     * @param int $isUpdate
     *
     * @return AdapayMember
     */
    public function setIsUpdate($isUpdate)
    {
        $this->is_update = $isUpdate;

        return $this;
    }

    /**
     * Get isUpdate.
     *
     * @return int
     */
    public function getIsUpdate()
    {
        return $this->is_update;
    }

    /**
     * Set isCreated.
     *
     * @param int $isCreated
     *
     * @return AdapayMember
     */
    public function setIsCreated($isCreated)
    {
        $this->is_created = $isCreated;

        return $this;
    }

    /**
     * Get isCreated.
     *
     * @return int
     */
    public function getIsCreated()
    {
        return $this->is_created;
    }
}
