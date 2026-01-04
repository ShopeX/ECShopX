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

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemsMedicine 商品药品数据表
 *
 * @ORM\Table(name="items_medicine",options={"comment"="商品药品数据表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_medicine_type", columns={"medicine_type"}),
 *    @ORM\Index(name="ix_common_name", columns={"common_name"}),
 *    @ORM\Index(name="ix_approval_number", columns={"approval_number"}),
 *    @ORM\Index(name="ix_is_prescription", columns={"is_prescription"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsMedicineRepository")
 */
class ItemsMedicine
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="medicine_type", type="smallint", options={"comment":"药品分类:0为西药，1为中成药，3为其他"})
     */
    private $medicine_type;

    /**
     * @var string
     *
     * @ORM\Column(name="common_name", type="string", options={"comment":"通用名（重要，具体请查看《同步药品信息规范指引》文档）"})
     */
    private $common_name;

    /**
     * @var string
     *
     * @ORM\Column(name="dosage", type="string", options={"comment":"剂型", "default": ""})
     */
    private $dosage = '';

    /**
     * @var string
     *
     * @ORM\Column(name="spec", type="string", options={"comment":"规格（重要，具体请查看《同步药品信息规范指引》文档）"})
     */
    private $spec;

    /**
     * @var string
     *
     * @ORM\Column(name="packing_spec", type="string", options={"comment":"包装规格", "default": ""})
     */
    private $packing_spec = '';

    /**
     * @var string
     *
     * @ORM\Column(name="manufacturer", type="string", options={"comment":"生产厂家"})
     */
    private $manufacturer;

    /**
     * @var string
     *
     * @ORM\Column(name="approval_number", type="string", options={"comment":"批准文号"})
     */
    private $approval_number;

    /**
     * @var string
     *
     * @ORM\Column(name="unit", type="string", options={"comment":"最小售卖单位"})
     */
    private $unit;

    /**
     * @var string
     *
     * @ORM\Column(name="is_prescription", type="smallint", options={"comment":"是否处方药（1为是，0为否）"})
     */
    private $is_prescription;

    /**
     * @var string
     *
     * @ORM\Column(name="special_common_name", type="string", options={"comment":"特殊通用名", "default": ""})
     */
    private $special_common_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="special_spec", type="string", options={"comment":"特殊规格", "default": ""})
     */
    private $special_spec = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="audit_status", type="smallint", options={"comment":"审核状态，0不需要审核（非处方药），1未审核，2审核通过，3审核不通过", "default": 1})
     */
    private $audit_status = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_reason", type="string", options={"comment":"审核不通过原因", "default": ""})
     */
    private $audit_reason = '';

    /**
     * @var string
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"商品类型 normal普通商品 point积分商品", "default": "normal"})
     */
    private $item_type = 'normal';

    /**
     * @var string
     *
     * @ORM\Column(name="use_tip", type="string", options={"comment":"处方药用药提示", "default": ""})
     */
    private $use_tip = '';

    /**
     * @var string
     *
     * @ORM\Column(name="symptom", type="string", options={"comment":"处方药适用症状", "default": ""})
     */
    private $symptom = '';

    /**
     * @var int
     *
     * @ORM\Column(name="max_num", type="integer", options={"comment":"处方药单次下单最大购买数量", "default": 0})
     */
    private $max_num = 0;

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
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return ItemsMedicine
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ItemsMedicine
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
     * Set medicineType.
     *
     * @param int $medicineType
     *
     * @return ItemsMedicine
     */
    public function setMedicineType($medicineType)
    {
        $this->medicine_type = $medicineType;

        return $this;
    }

    /**
     * Get medicineType.
     *
     * @return int
     */
    public function getMedicineType()
    {
        return $this->medicine_type;
    }

    /**
     * Set commonName.
     *
     * @param string $commonName
     *
     * @return ItemsMedicine
     */
    public function setCommonName($commonName)
    {
        $this->common_name = $commonName;

        return $this;
    }

    /**
     * Get commonName.
     *
     * @return string
     */
    public function getCommonName()
    {
        return $this->common_name;
    }

    /**
     * Set dosage.
     *
     * @param string $dosage
     *
     * @return ItemsMedicine
     */
    public function setDosage($dosage)
    {
        $this->dosage = $dosage;

        return $this;
    }

    /**
     * Get dosage.
     *
     * @return string
     */
    public function getDosage()
    {
        return $this->dosage;
    }

    /**
     * Set spec.
     *
     * @param string $spec
     *
     * @return ItemsMedicine
     */
    public function setSpec($spec)
    {
        $this->spec = $spec;

        return $this;
    }

    /**
     * Get spec.
     *
     * @return string
     */
    public function getSpec()
    {
        return $this->spec;
    }

    /**
     * Set packingSpec.
     *
     * @param string $packingSpec
     *
     * @return ItemsMedicine
     */
    public function setPackingSpec($packingSpec)
    {
        $this->packing_spec = $packingSpec;

        return $this;
    }

    /**
     * Get packingSpec.
     *
     * @return string
     */
    public function getPackingSpec()
    {
        return $this->packing_spec;
    }

    /**
     * Set manufacturer.
     *
     * @param string $manufacturer
     *
     * @return ItemsMedicine
     */
    public function setManufacturer($manufacturer)
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * Get manufacturer.
     *
     * @return string
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * Set approvalNumber.
     *
     * @param string $approvalNumber
     *
     * @return ItemsMedicine
     */
    public function setApprovalNumber($approvalNumber)
    {
        $this->approval_number = $approvalNumber;

        return $this;
    }

    /**
     * Get approvalNumber.
     *
     * @return string
     */
    public function getApprovalNumber()
    {
        return $this->approval_number;
    }

    /**
     * Set unit.
     *
     * @param string $unit
     *
     * @return ItemsMedicine
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit.
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set isPrescription.
     *
     * @param int $isPrescription
     *
     * @return ItemsMedicine
     */
    public function setIsPrescription($isPrescription)
    {
        $this->is_prescription = $isPrescription;

        return $this;
    }

    /**
     * Get isPrescription.
     *
     * @return int
     */
    public function getIsPrescription()
    {
        return $this->is_prescription;
    }

    /**
     * Set specialCommonName.
     *
     * @param string $specialCommonName
     *
     * @return ItemsMedicine
     */
    public function setSpecialCommonName($specialCommonName)
    {
        $this->special_common_name = $specialCommonName;

        return $this;
    }

    /**
     * Get specialCommonName.
     *
     * @return string
     */
    public function getSpecialCommonName()
    {
        return $this->special_common_name;
    }

    /**
     * Set specialSpec.
     *
     * @param string $specialSpec
     *
     * @return ItemsMedicine
     */
    public function setSpecialSpec($specialSpec)
    {
        $this->special_spec = $specialSpec;

        return $this;
    }

    /**
     * Get specialSpec.
     *
     * @return string
     */
    public function getSpecialSpec()
    {
        return $this->special_spec;
    }

    /**
     * Set auditStatus.
     *
     * @param int $auditStatus
     *
     * @return ItemsMedicine
     */
    public function setAuditStatus($auditStatus)
    {
        $this->audit_status = $auditStatus;

        return $this;
    }

    /**
     * Get auditStatus.
     *
     * @return int
     */
    public function getAuditStatus()
    {
        return $this->audit_status;
    }

    /**
     * Set auditReason.
     *
     * @param string $auditReason
     *
     * @return ItemsMedicine
     */
    public function setAuditReason($auditReason)
    {
        $this->audit_reason = $auditReason;

        return $this;
    }

    /**
     * Get auditReason.
     *
     * @return string
     */
    public function getAuditReason()
    {
        return $this->audit_reason;
    }

    /**
     * Set itemType.
     *
     * @param string $itemType
     *
     * @return ItemsMedicine
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get itemType.
     *
     * @return string
     */
    public function getItemType()
    {
        return $this->item_type;
    }

    /**
     * Set useTip.
     *
     * @param string $useTip
     *
     * @return ItemsMedicine
     */
    public function setUseTip($useTip)
    {
        $this->use_tip = $useTip;

        return $this;
    }

    /**
     * Get useTip.
     *
     * @return string
     */
    public function getUseTip()
    {
        return $this->use_tip;
    }

    /**
     * Set symptom.
     *
     * @param string $symptom
     *
     * @return ItemsMedicine
     */
    public function setSymptom($symptom)
    {
        $this->symptom = $symptom;

        return $this;
    }

    /**
     * Get symptom.
     *
     * @return string
     */
    public function getSymptom()
    {
        return $this->symptom;
    }

    /**
     * Set maxNum.
     *
     * @param int $maxNum
     *
     * @return ItemsMedicine
     */
    public function setMaxNum($maxNum)
    {
        $this->max_num = $maxNum;

        return $this;
    }

    /**
     * Get maxNum.
     *
     * @return int
     */
    public function getMaxNum()
    {
        return $this->max_num;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ItemsMedicine
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
     * @return ItemsMedicine
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
}
