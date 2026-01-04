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

namespace AliyunsmsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Sign 阿里云短信签名
 *
 * @ORM\Table(name="aliyunsms_sign", options={"comment":"短信签名表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AliyunsmsBundle\Repositories\SignRepository")
 */
class Sign
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sign_name", type="string", length=20, options={"comment":"签名名称"})
     */
    private $sign_name;

    /**
     * @var string
     * 0：企事业单位的全称或简称;1：工信部备案网站的全称或简称;2：App应用的全称或简称;3：公众号或小程序的全称或简称;4：电商平台店铺名的全称或简称;5：商标名的全称或简称
     * @ORM\Column(name="sign_source", type="string", length=2, options={"comment":"签名来源"})
     */
    private $sign_source;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", options={"comment":"签名申请说明"})
     */
    private $remark;

    /**
     * @var string
     *
     * @ORM\Column(name="sign_file", nullable=true, type="text", options={"comment":"资质证明"})
     */
    private $sign_file;

    /**
     * @var string
     *
     * @ORM\Column(name="delegate_file", nullable=true, type="text", options={"comment":"委托授权书"})
     */
    private $delegate_file;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=2, options={"comment":"审核状态:0-审核中;1-审核通过;2-审核失败"})
     */

    private $status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", nullable=true, type="string", options={"comment":"审核备注"})
     */
    private $reason = '';
    

    /**
     * @var integer
     *
     * @ORM\Column(name="third_party", type="integer", options={"comment":"签名用途 1:他用;0:自用", "default": 0})
     */
    private $third_party = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="qualification_id", nullable=true, type="string", options={"comment":"资质ID"})
     */
    private $qualification_id = '';

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
     * @return Sign
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
     * Set signName.
     *
     * @param string $signName
     *
     * @return Sign
     */
    public function setSignName($signName)
    {
        // XXX: review this code
        $this->sign_name = $signName;

        return $this;
    }

    /**
     * Get signName.
     *
     * @return string
     */
    public function getSignName()
    {
        return $this->sign_name;
    }

    /**
     * Set signSource.
     *
     * @param string $signSource
     *
     * @return Sign
     */
    public function setSignSource($signSource)
    {
        $this->sign_source = $signSource;

        return $this;
    }

    /**
     * Get signSource.
     *
     * @return string
     */
    public function getSignSource()
    {
        return $this->sign_source;
    }

    /**
     * Set remark.
     *
     * @param string $remark
     *
     * @return Sign
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark.
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set signFile.
     *
     * @param string $signFile
     *
     * @return Sign
     */
    public function setSignFile($signFile)
    {
        $this->sign_file = $signFile;

        return $this;
    }

    /**
     * Get signFile.
     *
     * @return string
     */
    public function getSignFile()
    {
        return $this->sign_file;
    }

    /**
     * Get delegateFile.
     *
     * @return string
     */
    public function getDelegateFile()
    {
        return $this->delegate_file;
    }

    /**
     * Set delegateFile.
     *
     * @param string $delegateFile
     *
     * @return Sign
     */
    public function setDelegateFile($delegateFile)
    {
        $this->delegate_file = $delegateFile;

        return $this;
    }


    /**
     * Set status.
     *
     * @param string $status
     *
     * @return Sign
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
     * Get reason.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set reason.
     *
     * @param string $reason
     *
     * @return Sign
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }


    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Sign
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
     * @return Sign
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

    /**
     * Set thirdParty.
     *
     * @param int $thirdParty
     *
     * @return Sign
     */
    public function setThirdParty($thirdParty)
    {
        $this->third_party = $thirdParty;

        return $this;
    }

    /**
     * Get thirdParty.
     *
     * @return int
     */
    public function getThirdParty()
    {
        return $this->third_party;
    }

    /**
     * Set qualificationId.
     *
     * @param string|null $qualificationId
     *
     * @return Sign
     */
    public function setQualificationId($qualificationId = null)
    {
        $this->qualification_id = $qualificationId;

        return $this;
    }

    /**
     * Get qualificationId.
     *
     * @return string|null
     */
    public function getQualificationId()
    {
        return $this->qualification_id;
    }
}
