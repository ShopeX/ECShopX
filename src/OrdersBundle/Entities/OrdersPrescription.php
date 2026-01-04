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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OrdersPrescription 处方药问诊单开方数据
 *
 * @ORM\Table(name="orders_prescription", options={"comment":"问诊单开方数据"},
 *     indexes={
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_diagnosis_id", columns={"diagnosis_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *         @ORM\Index(name="idx_status", columns={"status"}),
 *         @ORM\Index(name="idx_is_deleted", columns={"is_deleted"}),
 *         @ORM\Index(name="idx_user_family_name", columns={"user_family_name"}),
 *         @ORM\Index(name="idx_user_family_phone", columns={"user_family_phone"}),
 *         @ORM\Index(name="idx_user_family_id_card", columns={"user_family_id_card"}),
 *         @ORM\Index(name="idx_doctor_name", columns={"doctor_name"}),
 *         @ORM\Index(name="idx_serial_no", columns={"serial_no"}),
 *         @ORM\Index(name="idx_audit_apothecary_name", columns={"audit_apothecary_name"}),
 *         @ORM\Index(name="idx_audit_status", columns={"audit_status"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrdersPrescriptionRepository")
 */
class OrdersPrescription
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", length=64, options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="diagnosis_id", type="bigint", options={"comment":"问诊单id"})
     */
    private $diagnosis_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="prescription_id", type="bigint", options={"comment":"快诊580处方ID"})
     */
    private $prescription_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hospital_name", type="string", options={"comment":"互联网医院名称"})
     */
    private $hospital_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="kuaizhen_store_id", type="bigint", options={"comment":"580门店Id"})
     */
    private $kuaizhen_store_id;

    /**
     * @var string
     *
     * @ORM\Column(name="kuaizhen_store_name", type="string", options={"comment":"580门店名称"})
     */
    private $kuaizhen_store_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="kuaizhen_diagnosis_id", type="bigint", options={"comment":"580问诊单ID"})
     */
    private $kuaizhen_diagnosis_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="doctor_sign_time", type="bigint", options={"comment":"医生签署时间(时间戳)"})
     */
    private $doctor_sign_time;

    /**
     * @var string
     *
     * @ORM\Column(name="doctor_office", type="string", options={"comment":"医生科室"})
     */
    private $doctor_office;

    /**
     * @var integer
     *
     * @ORM\Column(name="doctor_id", type="bigint", options={"comment":"医生id"})
     */
    private $doctor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="doctor_name", type="string", options={"comment":"医生姓名"})
     */
    private $doctor_name;

    /**
     * @var string
     *
     * @ORM\Column(name="user_family_name", type="string", options={"comment":"就诊人姓名"})
     */
    private $user_family_name;

    /**
     * @var string
     *
     * @ORM\Column(name="user_family_phone", type="string", options={"comment":"就诊人手机号码", "default": ""})
     */
    private $user_family_phone = '';

    /**
     * @var int
     *
     * @ORM\Column(name="user_family_age", type="integer", options={"comment":"就诊人年龄"})
     */
    private $user_family_age;

    /**
     * @var int
     *
     * @ORM\Column(name="user_family_gender", type="smallint", options={"comment":"就诊人性别，0未知，1男，2女"})
     */
    private $user_family_gender;

    /**
     * @var string
     *
     * @ORM\Column(name="user_family_id_card", type="string", options={"comment":"用药人身份证号", "default": ""})
     */
    private $user_family_id_card = '';

    /**
     * @var int
     *
     * @ORM\Column(name="tags", type="string", options={"comment":"诊断标签"})
     */
    private $tags;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"comment":"处方状态(1正常 2已作废)"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="memo", type="string", options={"comment":"备注", "default": ""})
     */
    private $memo = '';

    /**
     * @var string
     *
     * @ORM\Column(name="remarks", type="string", options={"comment":"补充说明", "default": ""})
     */
    private $remarks = '';

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", options={"comment":"审核不通过的理由（可能为空）", "default": ""})
     */
    private $reason = '';

    /**
     * @var string
     *
     * @ORM\Column(name="dst_file_path", type="string", options={"comment":"处方图片地址"})
     */
    private $dst_file_path;

    /**
     * @var string
     *
     * @ORM\Column(name="serial_no", type="string", options={"comment":"处方编号"})
     */
    private $serial_no;

    /**
     * @var string
     *
     * @ORM\Column(name="drug_rsp_list", type="text", nullable=true, options={"comment":"药品信息说明"})
     */
    private $drug_rsp_list;

    /**
     * @var int
     *
     * @ORM\Column(name="audit_status", type="smallint", options={"comment": "处方审核状态，1未审核，2审核通过，3审核不通过，4不需要审方", "default": 1})
     */
    private $audit_status = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="audit_time", type="integer", options={"comment": "审方时间", "default": 0})
     */
    private $audit_time = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_reason", type="string", options={"comment": "审方不通过原因", "default": ""})
     */
    private $audit_reason = '';

    /**
     * @var string
     *
     * @ORM\Column(name="audit_apothecary_name", type="string", options={"comment": "审方药师名称", "default": ""})
     */
    private $audit_apothecary_name = '';

    /**
     * @var int
     *
     * @ORM\Column(name="is_deleted", type="smallint", options={"comment": "是否已废弃, 0否  1是", "default": 0})
     */
    private $is_deleted = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="delete_time", type="integer", options={"comment": "废弃时间", "default": 0})
     */
    private $delete_time = 0;

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
     * Set orderId.
     *
     * @param string $orderId
     *
     * @return OrdersPrescription
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set diagnosisId.
     *
     * @param int $diagnosisId
     *
     * @return OrdersPrescription
     */
    public function setDiagnosisId($diagnosisId)
    {
        $this->diagnosis_id = $diagnosisId;

        return $this;
    }

    /**
     * Get diagnosisId.
     *
     * @return int
     */
    public function getDiagnosisId()
    {
        return $this->diagnosis_id;
    }

    /**
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return OrdersPrescription
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OrdersPrescription
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return OrdersPrescription
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
     * Set prescriptionId.
     *
     * @param int $prescriptionId
     *
     * @return OrdersPrescription
     */
    public function setPrescriptionId($prescriptionId)
    {
        $this->prescription_id = $prescriptionId;

        return $this;
    }

    /**
     * Get prescriptionId.
     *
     * @return int
     */
    public function getPrescriptionId()
    {
        return $this->prescription_id;
    }

    /**
     * Set hospitalName.
     *
     * @param string $hospitalName
     *
     * @return OrdersPrescription
     */
    public function setHospitalName($hospitalName)
    {
        $this->hospital_name = $hospitalName;

        return $this;
    }

    /**
     * Get hospitalName.
     *
     * @return string
     */
    public function getHospitalName()
    {
        return $this->hospital_name;
    }

    /**
     * Set kuaizhenStoreId.
     *
     * @param int $kuaizhenStoreId
     *
     * @return OrdersPrescription
     */
    public function setKuaizhenStoreId($kuaizhenStoreId)
    {
        $this->kuaizhen_store_id = $kuaizhenStoreId;

        return $this;
    }

    /**
     * Get kuaizhenStoreId.
     *
     * @return int
     */
    public function getKuaizhenStoreId()
    {
        return $this->kuaizhen_store_id;
    }

    /**
     * Set kuaizhenStoreName.
     *
     * @param string $kuaizhenStoreName
     *
     * @return OrdersPrescription
     */
    public function setKuaizhenStoreName($kuaizhenStoreName)
    {
        $this->kuaizhen_store_name = $kuaizhenStoreName;

        return $this;
    }

    /**
     * Get kuaizhenStoreName.
     *
     * @return string
     */
    public function getKuaizhenStoreName()
    {
        return $this->kuaizhen_store_name;
    }

    /**
     * Set kuaizhenDiagnosisId.
     *
     * @param int $kuaizhenDiagnosisId
     *
     * @return OrdersPrescription
     */
    public function setKuaizhenDiagnosisId($kuaizhenDiagnosisId)
    {
        $this->kuaizhen_diagnosis_id = $kuaizhenDiagnosisId;

        return $this;
    }

    /**
     * Get kuaizhenDiagnosisId.
     *
     * @return int
     */
    public function getKuaizhenDiagnosisId()
    {
        return $this->kuaizhen_diagnosis_id;
    }

    /**
     * Set doctorSignTime.
     *
     * @param int $doctorSignTime
     *
     * @return OrdersPrescription
     */
    public function setDoctorSignTime($doctorSignTime)
    {
        $this->doctor_sign_time = $doctorSignTime;

        return $this;
    }

    /**
     * Get doctorSignTime.
     *
     * @return int
     */
    public function getDoctorSignTime()
    {
        return $this->doctor_sign_time;
    }

    /**
     * Set doctorOffice.
     *
     * @param string $doctorOffice
     *
     * @return OrdersPrescription
     */
    public function setDoctorOffice($doctorOffice)
    {
        $this->doctor_office = $doctorOffice;

        return $this;
    }

    /**
     * Get doctorOffice.
     *
     * @return string
     */
    public function getDoctorOffice()
    {
        return $this->doctor_office;
    }

    /**
     * Set doctorId.
     *
     * @param int $doctorId
     *
     * @return OrdersPrescription
     */
    public function setDoctorId($doctorId)
    {
        $this->doctor_id = $doctorId;

        return $this;
    }

    /**
     * Get doctorId.
     *
     * @return int
     */
    public function getDoctorId()
    {
        return $this->doctor_id;
    }

    /**
     * Set doctorName.
     *
     * @param string $doctorName
     *
     * @return OrdersPrescription
     */
    public function setDoctorName($doctorName)
    {
        $this->doctor_name = $doctorName;

        return $this;
    }

    /**
     * Get doctorName.
     *
     * @return string
     */
    public function getDoctorName()
    {
        return $this->doctor_name;
    }

    /**
     * Set userFamilyName.
     *
     * @param string $userFamilyName
     *
     * @return OrdersPrescription
     */
    public function setUserFamilyName($userFamilyName)
    {
        $this->user_family_name = $userFamilyName;

        return $this;
    }

    /**
     * Get userFamilyName.
     *
     * @return string
     */
    public function getUserFamilyName()
    {
        return $this->user_family_name;
    }

    /**
     * Set userFamilyPhone.
     *
     * @param string $userFamilyPhone
     *
     * @return OrdersPrescription
     */
    public function setUserFamilyPhone($userFamilyPhone)
    {
        $this->user_family_phone = $userFamilyPhone;

        return $this;
    }

    /**
     * Get userFamilyPhone.
     *
     * @return string
     */
    public function getUserFamilyPhone()
    {
        return $this->user_family_phone;
    }

    /**
     * Set userFamilyAge.
     *
     * @param int $userFamilyAge
     *
     * @return OrdersPrescription
     */
    public function setUserFamilyAge($userFamilyAge)
    {
        $this->user_family_age = $userFamilyAge;

        return $this;
    }

    /**
     * Get userFamilyAge.
     *
     * @return int
     */
    public function getUserFamilyAge()
    {
        return $this->user_family_age;
    }

    /**
     * Set userFamilyGender.
     *
     * @param int $userFamilyGender
     *
     * @return OrdersPrescription
     */
    public function setUserFamilyGender($userFamilyGender)
    {
        $this->user_family_gender = $userFamilyGender;

        return $this;
    }

    /**
     * Get userFamilyGender.
     *
     * @return int
     */
    public function getUserFamilyGender()
    {
        return $this->user_family_gender;
    }

    /**
     * Set tags.
     *
     * @param string $tags
     *
     * @return OrdersPrescription
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get tags.
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return OrdersPrescription
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set memo.
     *
     * @param string $memo
     *
     * @return OrdersPrescription
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * Get memo.
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * Set remarks.
     *
     * @param string $remarks
     *
     * @return OrdersPrescription
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks.
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set reason.
     *
     * @param string $reason
     *
     * @return OrdersPrescription
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
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
     * Set dstFilePath.
     *
     * @param string $dstFilePath
     *
     * @return OrdersPrescription
     */
    public function setDstFilePath($dstFilePath)
    {
        $this->dst_file_path = $dstFilePath;

        return $this;
    }

    /**
     * Get dstFilePath.
     *
     * @return string
     */
    public function getDstFilePath()
    {
        return $this->dst_file_path;
    }

    /**
     * Set serialNo.
     *
     * @param string $serialNo
     *
     * @return OrdersPrescription
     */
    public function setSerialNo($serialNo)
    {
        $this->serial_no = $serialNo;

        return $this;
    }

    /**
     * Get serialNo.
     *
     * @return string
     */
    public function getSerialNo()
    {
        return $this->serial_no;
    }

    /**
     * Set drugRspList.
     *
     * @param string|null $drugRspList
     *
     * @return OrdersPrescription
     */
    public function setDrugRspList($drugRspList = null)
    {
        $this->drug_rsp_list = $drugRspList;

        return $this;
    }

    /**
     * Get drugRspList.
     *
     * @return string|null
     */
    public function getDrugRspList()
    {
        return $this->drug_rsp_list;
    }

    /**
     * Set auditStatus.
     *
     * @param int $auditStatus
     *
     * @return OrdersPrescription
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
     * Set auditTime.
     *
     * @param int $auditTime
     *
     * @return OrdersPrescription
     */
    public function setAuditTime(int $auditTime): OrdersPrescription
    {
        $this->audit_time = $auditTime;
        return $this;
    }

    /**
     * Get auditTime.
     *
     * @return int
     */
    public function getAuditTime(): int
    {
        return $this->audit_time;
    }


    /**
     * Set auditReason.
     *
     * @param string $auditReason
     *
     * @return OrdersPrescription
     */
    public function setAuditReason(string $auditReason): OrdersPrescription
    {
        $this->audit_reason = $auditReason;
        return $this;
    }

    /**
     * Get auditReason.
     *
     * @return string
     */
    public function getAuditReason(): string
    {
        return $this->audit_reason;
    }

    /**
     * Set auditApothecaryName.
     *
     * @param string $auditApothecaryName
     *
     * @return OrdersPrescription
     */
    public function setAuditApothecaryName(string $auditApothecaryName): OrdersPrescription
    {
        $this->audit_apothecary_name = $auditApothecaryName;
        return $this;
    }

    /**
     * Get auditApothecaryName.
     *
     * @return string
     */
    public function getAuditApothecaryName(): string
    {
        return $this->audit_apothecary_name;
    }

    /**
     * Set isDeleted.
     *
     * @param int $isDeleted
     *
     * @return OrdersPrescription
     */
    public function setIsDeleted(int $isDeleted): OrdersPrescription
    {
        $this->is_deleted = $isDeleted;
        return $this;
    }

    /**
     * Get isDeleted.
     *
     * @return int
     */
    public function getIsDeleted(): int
    {
        return $this->is_deleted;
    }

    /**
     * Set deleteTime.
     *
     * @param int $deleteTime
     *
     * @return OrdersPrescription
     */
    public function setDeleteTime(int $deleteTime): OrdersPrescription
    {
        $this->delete_time = $deleteTime;
        return $this;
    }

    /**
     * Get deleteTime.
     *
     * @return int
     */
    public function getDeleteTime(): int
    {
        return $this->delete_time;
    }

    /**
     * Set userFamilyIdCard.
     *
     * @param string $userFamilyIdCard
     *
     * @return OrdersPrescription
     */
    public function setUserFamilyIdCard($userFamilyIdCard)
    {
        $this->user_family_id_card = $userFamilyIdCard;

        return $this;
    }

    /**
     * Get userFamilyIdCard.
     *
     * @return string
     */
    public function getUserFamilyIdCard()
    {
        return $this->user_family_id_card;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OrdersPrescription
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
     * @return OrdersPrescription
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
