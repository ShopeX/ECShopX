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
 * ChinaumspayDivisionUploadDetail 银联商务支付sftp上传明细
 *
 * @ORM\Table(name="chinaumspay_division_upload_detail", options={"comment":"银联商务支付sftp上传明细"},
 *     indexes={
 *         @ORM\Index(name="idx_company", columns={"company_id"}),
 *         @ORM\Index(name="idx_division_id", columns={"division_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="ChinaumsPayBundle\Repositories\ChinaumspayDivisionUploadDetailRepository")
 */
class ChinaumspayDivisionUploadDetail
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
     * @ORM\Column(name="division_id", type="bigint", options={"comment":"分账流水ID"})
     */
    private $division_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺ID"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="file_type", type="string", length=50, options={"comment":"文件类型 division:分账;transfer:划付;"})
     */
    private $file_type;

    /**
     * @var string
     *
     * @ORM\Column(name="detail", type="string", options={"comment":"分账明细"})
     */
    private $detail;

    /**
     * @var integer
     *
     * @ORM\Column(name="times", type="integer", options={"unsigned":true, "default":0, "comment":"上传次数"})
     */
    private $times;

    /**
     * @var integer
     *
     * @ORM\Column(name="backsucc_fee", type="integer", options={"unsigned":true, "default":0, "comment":"回盘成功金额，以分为单位"})
     */
    private $backsucc_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="rate_fee", type="integer", options={"unsigned":true, "default":0, "comment":"银联商务该笔指令收取的业务处理费，以分为单位"})
     */
    private $rate_fee = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="back_status", type="string", nullable=true, options={"comment":"回盘状态 0:未处理、1:处理中、2:成功、3:部分成功、4:失败"})
     */
    private $back_status;

    /**
     * @var string
     *
     * @ORM\Column(name="back_status_msg", type="string", nullable=true, options={"comment":"回盘状态描述"})
     */
    private $back_status_msg;

    /**
     * @var string
     *
     * @ORM\Column(name="chinaumspay_id", type="string", nullable=true, options={"comment":"银商内部ID"})
     */
    private $chinaumspay_id;

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
     * @return ChinaumspayDivisionUploadDetail
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
     * @return ChinaumspayDivisionUploadDetail
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return ChinaumspayDivisionUploadDetail
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
     * Set fileType.
     *
     * @param string $fileType
     *
     * @return ChinaumspayDivisionUploadDetail
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
     * Set detail.
     *
     * @param string $detail
     *
     * @return ChinaumspayDivisionUploadDetail
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get detail.
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Set times.
     *
     * @param int $times
     *
     * @return ChinaumspayDivisionUploadDetail
     */
    public function setTimes($times)
    {
        $this->times = $times;

        return $this;
    }

    /**
     * Get times.
     *
     * @return int
     */
    public function getTimes()
    {
        return $this->times;
    }

    /**
     * Set backsuccFee.
     *
     * @param int $backsuccFee
     *
     * @return ChinaumspayDivisionUploadDetail
     */
    public function setBacksuccFee($backsuccFee)
    {
        $this->backsucc_fee = $backsuccFee;

        return $this;
    }

    /**
     * Get backsuccFee.
     *
     * @return int
     */
    public function getBacksuccFee()
    {
        return $this->backsucc_fee;
    }

    /**
     * Set rateFee.
     *
     * @param int $rateFee
     *
     * @return ChinaumspayDivisionUploadDetail
     */
    public function setRateFee($rateFee)
    {
        $this->rate_fee = $rateFee;

        return $this;
    }

    /**
     * Get rateFee.
     *
     * @return int
     */
    public function getRateFee()
    {
        return $this->rate_fee;
    }

    /**
     * Set backStatus.
     *
     * @param string|null $backStatus
     *
     * @return ChinaumspayDivisionUploadDetail
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
     * Set backStatusMsg.
     *
     * @param string|null $backStatusMsg
     *
     * @return ChinaumspayDivisionUploadDetail
     */
    public function setBackStatusMsg($backStatusMsg = null)
    {
        $this->back_status_msg = $backStatusMsg;

        return $this;
    }

    /**
     * Get backStatusMsg.
     *
     * @return string|null
     */
    public function getBackStatusMsg()
    {
        return $this->back_status_msg;
    }

    /**
     * Set chinaumspayId.
     *
     * @param string|null $chinaumspayId
     *
     * @return ChinaumspayDivisionUploadDetail
     */
    public function setChinaumspayId($chinaumspayId = null)
    {
        $this->chinaumspay_id = $chinaumspayId;

        return $this;
    }

    /**
     * Get chinaumspayId.
     *
     * @return string|null
     */
    public function getChinaumspayId()
    {
        return $this->chinaumspay_id;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return ChinaumspayDivisionUploadDetail
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
     * @return ChinaumspayDivisionUploadDetail
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
