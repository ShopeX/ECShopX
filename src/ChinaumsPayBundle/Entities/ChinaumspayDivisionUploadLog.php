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

namespace ChinaumsPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ChinaumspayDivisionUploadLog 银联商务支付上传文件日志
 *
 * @ORM\Table(name="chinaumspay_division_upload_log", options={"comment":"银联商务支付上传文件日志"},
 *     indexes={
 *         @ORM\Index(name="idx_company", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="ChinaumsPayBundle\Repositories\ChinaumspayDivisionUploadLogRepository")
 */
class ChinaumspayDivisionUploadLog
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
     * @ORM\Column(name="file_type", type="string", length=50, options={"comment":"文件类型 division:分账;transfer:划付;"})
     */
    private $file_type;

    /**
     * @var string
     *
     * @ORM\Column(name="local_file_path", type="string", length=50, options={"comment":"本地文件路径"})
     */
    private $local_file_path;

    /**
     * @var string
     *
     * @ORM\Column(name="remote_file_path", type="string", length=50, options={"comment":"远程文件路径"})
     */
    private $remote_file_path;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=50, options={"comment":"文件名"})
     */
    private $file_name;

    /**
     * @var string
     *
     * @ORM\Column(name="file_content", type="text", options={"comment":"文件内容"})
     */
    private $file_content;

    /**
     * @var string
     *
     * @ORM\Column(name="back_status", type="string", nullable=true, options={"comment":"回盘状态 0:未回盘;1:已回盘;"})
     */
    private $back_status;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"订单创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"订单更新时间"})
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
     * @return ChinaumspayDivisionUploadLog
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
     * Set fileType.
     *
     * @param string $fileType
     *
     * @return ChinaumspayDivisionUploadLog
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
     * Set localFilePath.
     *
     * @param string $localFilePath
     *
     * @return ChinaumspayDivisionUploadLog
     */
    public function setLocalFilePath($localFilePath)
    {
        $this->local_file_path = $localFilePath;

        return $this;
    }

    /**
     * Get localFilePath.
     *
     * @return string
     */
    public function getLocalFilePath()
    {
        return $this->local_file_path;
    }

    /**
     * Set remoteFilePath.
     *
     * @param string $remoteFilePath
     *
     * @return ChinaumspayDivisionUploadLog
     */
    public function setRemoteFilePath($remoteFilePath)
    {
        $this->remote_file_path = $remoteFilePath;

        return $this;
    }

    /**
     * Get remoteFilePath.
     *
     * @return string
     */
    public function getRemoteFilePath()
    {
        return $this->remote_file_path;
    }

    /**
     * Set fileName.
     *
     * @param string $fileName
     *
     * @return ChinaumspayDivisionUploadLog
     */
    public function setFileName($fileName)
    {
        $this->file_name = $fileName;

        return $this;
    }

    /**
     * Get fileName.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * Set fileContent.
     *
     * @param string $fileContent
     *
     * @return ChinaumspayDivisionUploadLog
     */
    public function setFileContent($fileContent)
    {
        $this->file_content = $fileContent;

        return $this;
    }

    /**
     * Get fileContent.
     *
     * @return string
     */
    public function getFileContent()
    {
        return $this->file_content;
    }

    /**
     * Set backStatus.
     *
     * @param string|null $backStatus
     *
     * @return ChinaumspayDivisionUploadLog
     */
    public function setBackStatus($backStatus = null)
    {
        $this->back_status = $backStatus;

        return $this;
    }

    /**
     * Get backStatus.
     *
     * @return string|null
     */
    public function getBackStatus()
    {
        return $this->back_status;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return ChinaumspayDivisionUploadLog
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
     * @return ChinaumspayDivisionUploadLog
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
