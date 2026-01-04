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
 * OrdersDiagnosis 处方药问诊单
 *
 * @ORM\Table(name="orders_diagnosis", options={"comment":"问诊单"},
 *     indexes={
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *         @ORM\Index(name="idx_status", columns={"status"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrdersDiagnosisRepository")
 */
class OrdersDiagnosis
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
     * @ORM\Column(name="kuaizhen_store_id", type="bigint", options={"comment":"580门店Id"})
     */
    private $kuaizhen_store_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="service_type", type="smallint", options={"comment":"服务类型，0为图文，1为视频"})
     */
    private $service_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_examine", type="smallint", options={"comment":"是否需要审方（0为不需要，1为需要）"})
     */
    private $is_examine;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_pregnant_woman", type="smallint", options={"comment":"用药人是否孕妇（0为否，1为是）"})
     */
    private $is_pregnant_woman;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_lactation", type="smallint", options={"comment":"用药人是否哺乳期0为否，1为是"})
     */
    private $is_lactation;

    /**
     * @var integer
     *
     * @ORM\Column(name="souce_from", type="smallint", options={"comment":"来源（0为微信小程序，1为APP，2为H5，3为支付宝小程序）", "default": 0})
     */
    private $souce_from = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="user_family_name", type="string", options={"comment":"用药人姓名"})
     */
    private $user_family_name;

    /**
     * @var string
     *
     * @ORM\Column(name="user_family_id_card", type="string", options={"comment":"用药人身份证号", "default": ""})
     */
    private $user_family_id_card = '';

    /**
     * @var int
     *
     * @ORM\Column(name="user_family_age", type="integer", options={"comment":"用药人年龄"})
     */
    private $user_family_age;

    /**
     * @var int
     *
     * @ORM\Column(name="user_family_gender", type="smallint", options={"comment":"用药人性别1-男，2-女"})
     */
    private $user_family_gender;

    /**
     * @var int
     *
     * @ORM\Column(name="user_family_phone", type="string", options={"comment":"用药人手机号码"})
     */
    private $user_family_phone;

    /**
     * @var int
     *
     * @ORM\Column(name="relationship", type="smallint", options={"comment":"用药人与问诊人关系(1本人 2父母 3配偶 4子女 5其他)"})
     */
    private $relationship;

    /**
     * @var string
     *
     * @ORM\Column(name="before_ai_data_list", type="text", nullable=true, options={"comment":"AI问诊前5道题"})
     */
    private $before_ai_data_list;

    /**
     * @var int
     *
     * @ORM\Column(name="prescription_status", type="smallint", options={"comment":"开方状态，1未开方，2已开方，3医生拒绝开方", "default": 1})
     */
    private $prescription_status = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="prescription_refuse_reason", type="string", options={"comment":"拒绝开方原因", "default": ""})
     */
    private $prescription_refuse_reason = '';

    /**
     * @var string
     *
     * @ORM\Column(name="location_url", type="text", options={"comment":"跳转问诊H5页面地址", "default": ""})
     */
    private $location_url = '';

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"comment":"问诊单状态（1为进行中，2为已完成,5-AI问诊时取消）", "default": 1})
     */
    private $status = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"问诊结束时间", "default": 0})
     */
    private $end_time = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="cancel_time", type="integer", options={"comment":"问诊取消时间", "default": 0})
     */
    private $cancel_time = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="doctor_office", type="string", options={"comment":"医生科室", "default": ""})
     */
    private $doctor_office = "";

    /**
     * @var string
     *
     * @ORM\Column(name="doctor_name", type="string", options={"comment":"医生姓名", "default": ""})
     */
    private $doctor_name = "";

    /**
     * @var string
     *
     * @ORM\Column(name="hospital_name", type="string", options={"comment":"互联网医院名称", "default": ""})
     */
    private $hospital_name = "";

    /**
     * @var string
     *
     * @ORM\Column(name="first_visit_list", type="text", nullable=true, options={"comment":"首诊信息"})
     */
    private $first_visit_list;

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
     * @return OrdersDiagnosis
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
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return OrdersDiagnosis
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
     * @return OrdersDiagnosis
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
     * Set kuaizhenStoreId.
     *
     * @param int $kuaizhenStoreId
     *
     * @return OrdersDiagnosis
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return OrdersDiagnosis
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
     * Set serviceType.
     *
     * @param int $serviceType
     *
     * @return OrdersDiagnosis
     */
    public function setServiceType($serviceType)
    {
        $this->service_type = $serviceType;

        return $this;
    }

    /**
     * Get serviceType.
     *
     * @return int
     */
    public function getServiceType()
    {
        return $this->service_type;
    }

    /**
     * Set isExamine.
     *
     * @param int $isExamine
     *
     * @return OrdersDiagnosis
     */
    public function setIsExamine($isExamine)
    {
        $this->is_examine = $isExamine;

        return $this;
    }

    /**
     * Get isExamine.
     *
     * @return int
     */
    public function getIsExamine()
    {
        return $this->is_examine;
    }

    /**
     * Set isPregnantWoman.
     *
     * @param int $isPregnantWoman
     *
     * @return OrdersDiagnosis
     */
    public function setIsPregnantWoman($isPregnantWoman)
    {
        $this->is_pregnant_woman = $isPregnantWoman;

        return $this;
    }

    /**
     * Get isPregnantWoman.
     *
     * @return int
     */
    public function getIsPregnantWoman()
    {
        return $this->is_pregnant_woman;
    }

    /**
     * Set isLactation.
     *
     * @param int $isLactation
     *
     * @return OrdersDiagnosis
     */
    public function setIsLactation($isLactation)
    {
        $this->is_lactation = $isLactation;

        return $this;
    }

    /**
     * Get isLactation.
     *
     * @return int
     */
    public function getIsLactation()
    {
        return $this->is_lactation;
    }

    /**
     * Set souceFrom.
     *
     * @param int $souceFrom
     *
     * @return OrdersDiagnosis
     */
    public function setSouceFrom($souceFrom)
    {
        $this->souce_from = $souceFrom;

        return $this;
    }

    /**
     * Get souceFrom.
     *
     * @return int
     */
    public function getSouceFrom()
    {
        return $this->souce_from;
    }

    /**
     * Set userFamilyName.
     *
     * @param string $userFamilyName
     *
     * @return OrdersDiagnosis
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
     * Set userFamilyIdCard.
     *
     * @param string $userFamilyIdCard
     *
     * @return OrdersDiagnosis
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
     * Set userFamilyAge.
     *
     * @param int $userFamilyAge
     *
     * @return OrdersDiagnosis
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
     * @return OrdersDiagnosis
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
     * Set userFamilyPhone.
     *
     * @param string $userFamilyPhone
     *
     * @return OrdersDiagnosis
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
     * Set relationship.
     *
     * @param int $relationship
     *
     * @return OrdersDiagnosis
     */
    public function setRelationship($relationship)
    {
        $this->relationship = $relationship;

        return $this;
    }

    /**
     * Get relationship.
     *
     * @return int
     */
    public function getRelationship()
    {
        return $this->relationship;
    }

    /**
     * Set beforeAiDataList.
     *
     * @param string|null $beforeAiDataList
     *
     * @return OrdersDiagnosis
     */
    public function setBeforeAiDataList($beforeAiDataList = null)
    {
        $this->before_ai_data_list = $beforeAiDataList;

        return $this;
    }

    /**
     * Get beforeAiDataList.
     *
     * @return string|null
     */
    public function getBeforeAiDataList()
    {
        return $this->before_ai_data_list;
    }

    /**
     * Set prescriptionStatus.
     *
     * @param int $prescriptionStatus
     *
     * @return OrdersDiagnosis
     */
    public function setPrescriptionStatus($prescriptionStatus)
    {
        $this->prescription_status = $prescriptionStatus;

        return $this;
    }

    /**
     * Get prescriptionStatus.
     *
     * @return int
     */
    public function getPrescriptionStatus()
    {
        return $this->prescription_status;
    }

    /**
     * Set prescriptionRefuseReason.
     *
     * @param string $prescriptionRefuseReason
     *
     * @return OrdersDiagnosis
     */
    public function setPrescriptionRefuseReason($prescriptionRefuseReason)
    {
        $this->prescription_refuse_reason = $prescriptionRefuseReason;

        return $this;
    }

    /**
     * Get prescriptionRefuseReason.
     *
     * @return string
     */
    public function getPrescriptionRefuseReason()
    {
        return $this->prescription_refuse_reason;
    }

    /**
     * Set locationUrl.
     *
     * @param string $locationUrl
     *
     * @return OrdersDiagnosis
     */
    public function setLocationUrl(string $locationUrl): OrdersDiagnosis
    {
        $this->location_url = $locationUrl;
        return $this;
    }

    /**
     * Get locationUrl.
     *
     * @return string
     */
    public function getLocationUrl(): string
    {
        return $this->location_url;
    }


    /**
     * Set status.
     *
     * @param int $status
     *
     * @return OrdersDiagnosis
     */
    public function setStatus(int $status): OrdersDiagnosis
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set endTime.
     *
     * @param int $endTime
     *
     * @return OrdersDiagnosis
     */
    public function setEndTime(int $endTime): OrdersDiagnosis
    {
        $this->end_time = $endTime;
        return $this;
    }

    /**
     * Get endTime.
     *
     * @return int
     */
    public function getEndTime(): int
    {
        return $this->end_time;
    }

    /**
     * Set cancelTime.
     *
     * @param int $cancelTime
     *
     * @return OrdersDiagnosis
     */
    public function setCancelTime(int $cancelTime): OrdersDiagnosis
    {
        $this->cancel_time = $cancelTime;
        return $this;
    }

    /**
     * Get cancelTime.
     *
     * @return int
     */
    public function getCancelTime(): int
    {
        return $this->cancel_time;
    }

    /**
     * Set doctorOffice.
     *
     * @param string $doctorOffice
     *
     * @return OrdersDiagnosis
     */
    public function setDoctorOffice(string $doctorOffice): OrdersDiagnosis
    {
        $this->doctor_office = $doctorOffice;
        return $this;
    }

    /**
     * Get doctorOffice.
     *
     * @return string
     */
    public function getDoctorOffice(): string
    {
        return $this->doctor_office;
    }

    /**
     * Set doctorName.
     *
     * @param string $doctorName
     *
     * @return OrdersDiagnosis
     */
    public function setDoctorName(string $doctorName): OrdersDiagnosis
    {
        $this->doctor_name = $doctorName;
        return $this;
    }

    /**
     * Get doctorName.
     *
     * @return string
     */
    public function getDoctorName(): string
    {
        return $this->doctor_name;
    }

    /**
     * Set hospitalName.
     *
     * @param string $hospitalName
     *
     * @return OrdersDiagnosis
     */
    public function setHospitalName(string $hospitalName): OrdersDiagnosis
    {
        $this->hospital_name = $hospitalName;
        return $this;
    }

    /**
     * Get hospitalName.
     *
     * @return string
     */
    public function getHospitalName(): string
    {
        return $this->hospital_name;
    }

    /**
     * Set firstVisitList.
     *
     * @param string|null $firstVisitList
     * @return OrdersDiagnosis
     */
    public function setFirstVisitList($firstVisitList)
    {
        $this->first_visit_list = $firstVisitList;
        return $this;
    }

    /**
     * Get firstVisitList.
     *
     * @return string|null
     */
    public function getFirstVisitList()
    {
        return $this->first_visit_list;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OrdersDiagnosis
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
     * @return OrdersDiagnosis
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
