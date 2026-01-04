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
 * ChinaumspayDivisionErrorLog 银联商务支付分账错误日志
 *
 * @ORM\Table(name="chinaumspay_division_error_log", options={"comment":"银联商务支付分账错误日志"},
 *     indexes={
 *         @ORM\Index(name="idx_company", columns={"company_id"}),
 *         @ORM\Index(name="idx_division_id", columns={"division_id"}),
 *         @ORM\Index(name="idx_type", columns={"type"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="ChinaumsPayBundle\Repositories\ChinaumspayDivisionErrorLogRepository")
 */
class ChinaumspayDivisionErrorLog
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
     * @var integer
     *
     * @ORM\Column(name="division_id", type="bigint", length=64, options={"comment":"分账流水ID"})
     */
    private $division_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="upload_detail_id", type="bigint", length=64, options={"comment":"上传明细ID"})
     */
    private $upload_detail_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50, options={"comment":"类型 division:分账;transfer:划付;"})
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺ID"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=true, options={"comment":"错误状态 0:未处理、1:处理中、2:成功、3:部分成功、4:失败"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_desc", type="text", nullable=true, options={"comment":"错误描述"})
     */
    private $error_desc;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_resubmit", type="boolean", nullable=true, options={"comment":"是否重新提交", "default": false})
     */
    private $is_resubmit = false;

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
     * @return ChinaumspayDivisionErrorLog
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
     * Set divisionId.
     *
     * @param int $divisionId
     *
     * @return ChinaumspayDivisionErrorLog
     */
    public function setDivisionId($divisionId)
    {
        $this->division_id = $divisionId;

        return $this;
    }

    /**
     * Get divisionId.
     *
     * @return int
     */
    public function getDivisionId()
    {
        return $this->division_id;
    }

    /**
     * Set uploadDetailId.
     *
     * @param int $uploadDetailId
     *
     * @return ChinaumspayDivisionErrorLog
     */
    public function setUploadDetailId($uploadDetailId)
    {
        $this->upload_detail_id = $uploadDetailId;

        return $this;
    }

    /**
     * Get uploadDetailId.
     *
     * @return int
     */
    public function getUploadDetailId()
    {
        return $this->upload_detail_id;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return ChinaumspayDivisionErrorLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return ChinaumspayDivisionErrorLog
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return ChinaumspayDivisionErrorLog
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set errorDesc.
     *
     * @param string|null $errorDesc
     *
     * @return ChinaumspayDivisionErrorLog
     */
    public function setErrorDesc($errorDesc = null)
    {
        $this->error_desc = $errorDesc;

        return $this;
    }

    /**
     * Get errorDesc.
     *
     * @return string|null
     */
    public function getErrorDesc()
    {
        return $this->error_desc;
    }

    /**
     * Set isResubmit.
     *
     * @param bool|null $isResubmit
     *
     * @return ChinaumspayDivisionErrorLog
     */
    public function setIsResubmit($isResubmit = null)
    {
        $this->is_resubmit = $isResubmit;

        return $this;
    }

    /**
     * Get isResubmit.
     *
     * @return bool|null
     */
    public function getIsResubmit()
    {
        return $this->is_resubmit;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return ChinaumspayDivisionErrorLog
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
     * @return ChinaumspayDivisionErrorLog
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
