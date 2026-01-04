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
 * AdapayUploadLicense adapay上传商户证照
 *
 * @ORM\Table(name="adapay_upload_license", options={"comment":"adapay上传商户证照"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayUploadLicenseRepository")
 */
class AdapayUploadLicense
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
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_api_key", type="string", options={"comment":"渠道商下商户的apiKey"})
     */
    private $sub_api_key;

    /**
     * @var string
     *
     * @ORM\Column(name="file_url", type="text", options={"comment":"文件路径"})
     */
    private $file_url;

    /**
     * @var string
     *
     * @ORM\Column(name="file_type", type="string", length=10, options={"comment":"图片类型，01：三证合一码，02：法人/小微负责人身份证正面，03：法人/小微负责人身份证反面，04：门店，05：开户许可证/小微负责人银行卡正面照，06：股东身份证正面，07：股东身份证反面，08：结算账号开户证明，09：网站截图，10：行业资质文件，11：icp备案许可证明或者许可证编码，12：租赁合同，13：交易测试记录，14：业务场景证明材料"})
     */
    private $file_type;

    /**
     * @var string
     *
     * @ORM\Column(name="pic_id", type="string", options={"comment":"Adapay系统生成的图片id，作为 提交商户证照 接口的请求参数上送至Adapay"})
     */
    private $pic_id;

    /**
     * @var string
     *
     * @ORM\Column(name="error_msg", type="string", length=500, nullable=true, options={"comment":"错误描述"})
     */
    private $error_msg;

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
     * @param int $companyId
     *
     * @return AdapayUploadLicense
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
     * Set subApiKey.
     *
     * @param string $subApiKey
     *
     * @return AdapayUploadLicense
     */
    public function setSubApiKey($subApiKey)
    {
        $this->sub_api_key = $subApiKey;

        return $this;
    }

    /**
     * Get subApiKey.
     *
     * @return string
     */
    public function getSubApiKey()
    {
        return $this->sub_api_key;
    }

    /**
     * Set fileUrl.
     *
     * @param string $fileUrl
     *
     * @return AdapayUploadLicense
     */
    public function setFileUrl($fileUrl)
    {
        $this->file_url = $fileUrl;

        return $this;
    }

    /**
     * Get fileUrl.
     *
     * @return string
     */
    public function getFileUrl()
    {
        return $this->file_url;
    }

    /**
     * Set fileType.
     *
     * @param string $fileType
     *
     * @return AdapayUploadLicense
     */
    public function setFileType($fileType)
    {
        $this->file_type = $fileType;

        return $this;
    }

    /**
     * Get fileType.
     *
     * @return string
     */
    public function getFileType()
    {
        return $this->file_type;
    }

    /**
     * Set picId.
     *
     * @param string $picId
     *
     * @return AdapayUploadLicense
     */
    public function setPicId($picId)
    {
        $this->pic_id = $picId;

        return $this;
    }

    /**
     * Get picId.
     *
     * @return string
     */
    public function getPicId()
    {
        return $this->pic_id;
    }

    /**
     * Set errorMsg.
     *
     * @param string|null $errorMsg
     *
     * @return AdapayUploadLicense
     */
    public function setErrorMsg($errorMsg = null)
    {
        $this->error_msg = $errorMsg;

        return $this;
    }

    /**
     * Get errorMsg.
     *
     * @return string|null
     */
    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayUploadLicense
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
     * @return AdapayUploadLicense
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
}
