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

namespace EspierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UploadeFile 文件上传日志表
 *
 * @ORM\Table(name="espier_uploadefile", options={"comment":"上传文件日志ID"})
 * @ORM\Entity(repositoryClass="EspierBundle\Repositories\UploadeFileRepository")
 */

class UploadeFile
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"上传文件日志ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"操作者id", "default": 0})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商id", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", options={"comment":"上传文件名称"})
     */
    private $file_name;

    /**
     * @var string
     *
     * @ORM\Column(name="file_type", type="string", options={"comment":"上传文件类型"})
     */
    private $file_type;

    /**
     * @var string
     *
     * @ORM\Column(name="file_size", type="string", options={"comment":"上传文件大小"})
     */
    private $file_size;

    /**
     * @var string
     *
     * @ORM\Column(name="handle_status", type="string", options={"comment":"处理文件状态，可选值有，wait:等待处理"})
     */
    private $handle_status;

    /**
     * @var string
     *
     * @ORM\Column(name="handle_line_num", type="string", options={"comment":"处理文件行数"})
     */
    private $handle_line_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="finish_time", nullable=true, type="bigint", options={"comment":"处理完成时间"})
     */
    private $finish_time;

    /**
     * @var string
     *
     * @ORM\Column(name="handle_message", nullable=true, type="text", options={"comment":"处理错误文件"})
     */
    private $handle_message;

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
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"distributor_id", "default": 0})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="left_job_num", type="integer", options={"comment":"剩余子任务数", "default": 0})
     */
    private $left_job_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id = 0;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UploadeFile
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
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return UploadeFile
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
     * Set fileName
     *
     * @param string $fileName
     *
     * @return UploadeFile
     */
    public function setFileName($fileName)
    {
        $this->file_name = $fileName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * Set fileType
     *
     * @param string $fileType
     *
     * @return UploadeFile
     */
    public function setFileType($fileType)
    {
        $this->file_type = $fileType;

        return $this;
    }

    /**
     * Get fileType
     *
     * @return string
     */
    public function getFileType()
    {
        return $this->file_type;
    }

    /**
     * Set fileSize
     *
     * @param string $fileSize
     *
     * @return UploadeFile
     */
    public function setFileSize($fileSize)
    {
        $this->file_size = $fileSize;

        return $this;
    }

    /**
     * Get fileSize
     *
     * @return string
     */
    public function getFileSize()
    {
        return $this->file_size;
    }

    /**
     * Set handleStatus
     *
     * @param string $handleStatus
     *
     * @return UploadeFile
     */
    public function setHandleStatus($handleStatus)
    {
        $this->handle_status = $handleStatus;

        return $this;
    }

    /**
     * Get handleStatus
     *
     * @return string
     */
    public function getHandleStatus()
    {
        return $this->handle_status;
    }

    /**
     * Set handleLineNum
     *
     * @param string $handleLineNum
     *
     * @return UploadeFile
     */
    public function setHandleLineNum($handleLineNum)
    {
        $this->handle_line_num = $handleLineNum;

        return $this;
    }

    /**
     * Get handleLineNum
     *
     * @return string
     */
    public function getHandleLineNum()
    {
        return $this->handle_line_num;
    }

    /**
     * Set finishTime
     *
     * @param integer $finishTime
     *
     * @return UploadeFile
     */
    public function setFinishTime($finishTime)
    {
        $this->finish_time = $finishTime;

        return $this;
    }

    /**
     * Get finishTime
     *
     * @return integer
     */
    public function getFinishTime()
    {
        return $this->finish_time;
    }

    /**
     * Set handleMessage
     *
     * @param string $handleMessage
     *
     * @return UploadeFile
     */
    public function setHandleMessage($handleMessage)
    {
        $this->handle_message = $handleMessage;

        return $this;
    }

    /**
     * Get handleMessage
     *
     * @return string
     */
    public function getHandleMessage()
    {
        return $this->handle_message;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return UploadeFile
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return UploadeFile
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return UploadeFile
     */
    public function setDistributorId($distributorId = null)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set leftJobNum.
     *
     * @param int|null $leftJobNum
     *
     * @return UploadeFile
     */
    public function setLeftJobNum($leftJobNum = null)
    {
        $this->left_job_num = $leftJobNum;

        return $this;
    }

    /**
     * Get leftJobNum.
     *
     * @return int|null
     */
    public function getLeftJobNum()
    {
        return $this->left_job_num;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return ExportLog
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return UploadeFile
     */
    public function setSupplierId($supplierId = 0)
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    /**
     * Get supplierId.
     *
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }
}
