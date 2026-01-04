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
 * OrderEpidemicRegister  订单疫情登记表
 *
 * @ORM\Table(name="order_epidemic_register", options={"comment":"订单疫情登记表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 * })
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderEpidemicRegisterRepository")
 */
class OrderEpidemicRegister
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
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单id"})
     */
    private $order_id;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", length=64, options={"comment":"用户ID"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺ID"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", options={"comment":"登记姓名"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="text", options={"comment":"手机号"})
     */
    private $mobile;
    
    /**
     * @var string
     *
     * @ORM\Column(name="cert_id", type="text", options={"comment":"身份证号"})
     */
    private $cert_id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="temperature", type="string", length=30,options={"comment":"体温"})
     */
    private $temperature;
    
    /**
     * @var string
     *
     * @ORM\Column(name="job", type="string", length=100, options={"comment":"职业"})
     */
    private $job;
    
    /**
     * @var string
     *
     * @ORM\Column(name="symptom", type="string", length=50,options={"comment":"症状"})
     */
    private $symptom;

    /**
     * @var string
     *
     * @ORM\Column(name="symptom_des", type="string", nullable=true, length=500,options={"comment":"症状描述"})
     */
    private $symptom_des;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="is_risk_area", type="integer", options={"comment":"是否去过中高风险地区 1:是 0:否"})
     */
    private $is_risk_area;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="is_use", type="integer", options={"comment":"是否使用这条登记信息 1:是 0:否", "default":1})
     */
    private $is_use = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_time", type="integer", options={"comment":"下单时间"})
     */
    private $order_time;


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
     * @param int $orderId
     *
     * @return OrderEpidemicRegister
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return OrderEpidemicRegister
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
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
     * @return OrderEpidemicRegister
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
     * @return OrderEpidemicRegister
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
     * Set name.
     *
     * @param string $name
     *
     * @return OrderEpidemicRegister
     */
    public function setName($name)
    {
        $this->name = fixedencrypt($name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return fixeddecrypt($this->name);
    }

    /**
     * Set mobile.
     *
     * @param string $mobile
     *
     * @return OrderEpidemicRegister
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set certId.
     *
     * @param string $certId
     *
     * @return OrderEpidemicRegister
     */
    public function setCertId($certId)
    {
        $this->cert_id = fixedencrypt($certId);

        return $this;
    }

    /**
     * Get certId.
     *
     * @return string
     */
    public function getCertId()
    {
        return fixeddecrypt($this->cert_id);
    }

    /**
     * Set temperature.
     *
     * @param string $temperature
     *
     * @return OrderEpidemicRegister
     */
    public function setTemperature($temperature)
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Get temperature.
     *
     * @return string
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    /**
     * Set job.
     *
     * @param string $job
     *
     * @return OrderEpidemicRegister
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Get job.
     *
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set symptom.
     *
     * @param string $symptom
     *
     * @return OrderEpidemicRegister
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
     * Set symptomDes.
     *
     * @param string|null $symptomDes
     *
     * @return OrderEpidemicRegister
     */
    public function setSymptomDes($symptomDes = null)
    {
        $this->symptom_des = $symptomDes;

        return $this;
    }

    /**
     * Get symptomDes.
     *
     * @return string|null
     */
    public function getSymptomDes()
    {
        return $this->symptom_des;
    }

    /**
     * Set isRiskArea.
     *
     * @param int $isRiskArea
     *
     * @return OrderEpidemicRegister
     */
    public function setIsRiskArea($isRiskArea)
    {
        $this->is_risk_area = $isRiskArea;

        return $this;
    }

    /**
     * Get isRiskArea.
     *
     * @return int
     */
    public function getIsRiskArea()
    {
        return $this->is_risk_area;
    }

    /**
     * Set orderTime.
     *
     * @param int $orderTime
     *
     * @return OrderEpidemicRegister
     */
    public function setOrderTime($orderTime)
    {
        $this->order_time = $orderTime;

        return $this;
    }

    /**
     * Get orderTime.
     *
     * @return int
     */
    public function getOrderTime()
    {
        return $this->order_time;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OrderEpidemicRegister
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
     * @return OrderEpidemicRegister
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
     * Set isUse.
     *
     * @param int $isUse
     *
     * @return OrderEpidemicRegister
     */
    public function setIsUse($isUse)
    {
        $this->is_use = $isUse;

        return $this;
    }

    /**
     * Get isUse.
     *
     * @return int
     */
    public function getIsUse()
    {
        return $this->is_use;
    }
}
