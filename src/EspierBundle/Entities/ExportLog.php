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
 * ExportLog 导出日志表
 *
 * @ORM\Table(name="espier_export_log", options={"comment":"导出日志表"})
 * @ORM\Entity(repositoryClass="EspierBundle\Repositories\ExportLogRepository")
 */

class ExportLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="log_id", type="bigint", options={"comment":"导出日志id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $log_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", nullable=true, options={"comment":"导出文件名称"})
     */
    private $file_name;

    /**
     * @var string
     *
     * @ORM\Column(name="file_url", type="text", nullable=true, options={"comment":"导出文件下载路径"})
     */
    private $file_url;

    /**
     * @var string
     *
     * @ORM\Column(name="export_type", type="string", options={"comment":"导出类型  member:会员导出,order:订单导出,right:权益导出"})
     */
    private $export_type;

    /**
     * @var string
     *
     * @ORM\Column(name="handle_status", type="string", options={"comment":"处理文件状态，可选值有，wait:等待处理,finish:处理完成,processing:处理中,fail:失败"})
     */
    private $handle_status = 'wait';

    /**
     * @var string
     *
     * @ORM\Column(name="error_msg", type="text", nullable=true, options={"comment":"失败原因"})
     */
    private $error_msg;

    /**
     * @var integer
     *
     * @ORM\Column(name="finish_time", nullable=true, type="bigint", options={"comment":"处理完成时间"})
     */
    private $finish_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"default":0, "comment":"账号id"})
     */
    private $operator_id = 0;

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
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商id", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * Get logId
     *
     * @return integer
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ExportLog
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
     * Set fileName
     *
     * @param string $fileName
     *
     * @return ExportLog
     */
    public function setFileName($fileName)
    {
        // NOTE: important business logic
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
     * Set fileUrl
     *
     * @param string $fileUrl
     *
     * @return ExportLog
     */
    public function setFileUrl($fileUrl)
    {
        $this->file_url = $fileUrl;

        return $this;
    }

    /**
     * Get fileUrl
     *
     * @return string
     */
    public function getFileUrl()
    {
        return $this->file_url;
    }

    /**
     * Set exportType
     *
     * @param string $exportType
     *
     * @return ExportLog
     */
    public function setExportType($exportType)
    {
        $this->export_type = $exportType;

        return $this;
    }

    /**
     * Get exportType
     *
     * @return string
     */
    public function getExportType()
    {
        return $this->export_type;
    }

    /**
     * Set handleStatus
     *
     * @param string $handleStatus
     *
     * @return ExportLog
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
     * Set errorMsg
     *
     * @param string $errorMsg
     *
     * @return ExportLog
     */
    public function setErrorMsg($errorMsg)
    {
        $this->error_msg = $errorMsg;

        return $this;
    }

    /**
     * Get errorMsg
     *
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    /**
     * Set finishTime
     *
     * @param integer $finishTime
     *
     * @return ExportLog
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
     * Set created
     *
     * @param integer $created
     *
     * @return ExportLog
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
     * @return ExportLog
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return ExportLog
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

    /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return ExportLog
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int
     */
    public function getOperatorId()
    {
        return $this->operator_id;
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
     * @return ExportLog
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
