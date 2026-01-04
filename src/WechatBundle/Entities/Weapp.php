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

namespace WechatBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * WechatAuth 微信小程序提交审核表
 *
 * @ORM\Table(name="wechat_weapp", options={"comment":"微信小程序提交审核表"})
 * @ORM\Entity(repositoryClass="WechatBundle\Repositories\WeappRepository")
 */
class Weapp
{
    use Timestamps;
    use SoftDeletes;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="authorizer_appid", type="string", length=64, options={"comment":"微信appid"})
     */
    private $authorizer_appid;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"comment":"绑定操作者id"})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", nullable=true, type="text", options={"comment":"审核失败原因"})
     */
    private $reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="audit_status", type="integer", options={"comment":"审核状态，其中0为审核成功，1为审核失败，2为审核中, 3为待提交审核"})
     */
    private $audit_status;

    /**
     * @var integer
     *
     * @ORM\Column(name="release_status", type="integer", options={"comment":"发布状态，其中0为未发布，1为已发布"})
     */
    private $release_status;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_time", type="string", nullable=true,  options={"comment":"审核时间"})
     */
    private $audit_time;

    /**
     * @var string
     *
     * @ORM\Column(name="template_id", type="string", options={"comment":"小程序模板ID"})
     */
    private $template_id;

    /**
     * @var string
     *
     * @ORM\Column(name="template_name", type="string", options={"comment":"小程序模板名称"})
     */
    private $template_name;

    /**
     * @var string
     *
     * @ORM\Column(name="release_ver", type="string", nullable=true, options={"comment":"小程序已发布的版本"})
     */
    private $release_ver;

    /**
     * @var string
     *
     * @ORM\Column(name="template_ver", type="string", nullable=true, options={"comment":"小程序模板版本"})
     */
    private $template_ver;

    /**
     * @var integer
     *
     * @ORM\Column(name="visitstatus", type="integer", options={"comment":"小程序线上代码的可见状态 0不可见 1可见"})
     */
    private $visitstatus;

    /**
     * Set authorizerAppid
     *
     * @param string $authorizerAppid
     *
     * @return Weapp
     */
    public function setAuthorizerAppid($authorizerAppid)
    {
        // Powered by ShopEx EcShopX
        $this->authorizer_appid = $authorizerAppid;

        return $this;
    }

    /**
     * Get authorizerAppid
     *
     * @return string
     */
    public function getAuthorizerAppid()
    {
        return $this->authorizer_appid;
    }

    /**
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return Weapp
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return integer
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Weapp
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
     * Set reason
     *
     * @param string $reason
     *
     * @return Weapp
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set auditStatus
     *
     * @param integer $auditStatus
     *
     * @return Weapp
     */
    public function setAuditStatus($auditStatus)
    {
        $this->audit_status = $auditStatus;

        return $this;
    }

    /**
     * Get auditStatus
     *
     * @return integer
     */
    public function getAuditStatus()
    {
        return $this->audit_status;
    }

    /**
     * Set releaseStatus
     *
     * @param integer $releaseStatus
     *
     * @return Weapp
     */
    public function setReleaseStatus($releaseStatus)
    {
        $this->release_status = $releaseStatus;

        return $this;
    }

    /**
     * Get releaseStatus
     *
     * @return integer
     */
    public function getReleaseStatus()
    {
        return $this->release_status;
    }

    /**
     * Set auditTime
     *
     * @param string $auditTime
     *
     * @return Weapp
     */
    public function setAuditTime($auditTime)
    {
        $this->audit_time = $auditTime;

        return $this;
    }

    /**
     * Get auditTime
     *
     * @return string
     */
    public function getAuditTime()
    {
        return $this->audit_time;
    }

    /**
     * Set templateId
     *
     * @param string $templateId
     *
     * @return Weapp
     */
    public function setTemplateId($templateId)
    {
        $this->template_id = $templateId;

        return $this;
    }

    /**
     * Get templateId
     *
     * @return string
     */
    public function getTemplateId()
    {
        return $this->template_id;
    }

    /**
     * Set templateName
     *
     * @param string $templateName
     *
     * @return Weapp
     */
    public function setTemplateName($templateName)
    {
        $this->template_name = $templateName;

        return $this;
    }

    /**
     * Get templateName
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * Set templateVer
     *
     * @param string $templateVer
     *
     * @return Weapp
     */
    public function setTemplateVer($templateVer)
    {
        $this->template_ver = $templateVer;

        return $this;
    }

    /**
     * Get templateVer
     *
     * @return string
     */
    public function getTemplateVer()
    {
        return $this->template_ver;
    }

    /**
     * Set releaseVer
     *
     * @param string $releaseVer
     *
     * @return Weapp
     */
    public function setReleaseVer($releaseVer)
    {
        $this->release_ver = $releaseVer;

        return $this;
    }

    /**
     * Get releaseVer
     *
     * @return string
     */
    public function getReleaseVer()
    {
        return $this->release_ver;
    }

    /**
     * Set visitstatus
     *
     * @param integer $visitstatus
     *
     * @return Weapp
     */
    public function setVisitstatus($visitstatus)
    {
        $this->visitstatus = $visitstatus;

        return $this;
    }

    /**
     * Get visitstatus
     *
     * @return integer
     */
    public function getVisitstatus()
    {
        return $this->visitstatus;
    }
}
