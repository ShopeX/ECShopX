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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SelfDeliveryStaff 自配送员工信息表
 *
 * @ORM\Table(name="self_delivery_staff", options={"comment"="自配送员工信息表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_operator_id",   columns={"operator_id"}),
 * })
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\SelfDeliveryStaffRepository")
 */
class SelfDeliveryStaff
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
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;


    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint")
     */
    private $operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_id", type="integer",  nullable=true, options={"comment":"配送员所属店铺id","default": 0})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", type="integer",  nullable=true, options={"comment":"配送员所属门店id","default": 0})
     */
    private $shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="staff_attribute", type="string", options={"comment":"配送员属性。full_time:全职;part_time:兼职;", "default": "full_time"})
     */
    private $staff_attribute = "full_time";

    /**
     * @var string
     *
     * @ORM\Column(name="staff_no", type="string",nullable=true,  options={"comment":"配送员编号。"})
     */
    private $staff_no ;

    /**
     * @var string
     *
     * @ORM\Column(name="staff_type", type="string", options={"comment":"配送员类型。platform:平台;distributor:店铺;shop:商家;", "default": "distributor"})
     */
    private $staff_type = "distributor";

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", options={"comment":"结算方式。order:订单;amount:订单金额;", "default": "order"})
     */
    private $payment_method = "order";

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_fee", type="integer", options={"comment":"结合payment_method计算使用，结算费用 分 或 百分比", "default": 0})
     */
    private $payment_fee = 0;



    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SelfDeliveryStaff
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
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return SelfDeliveryStaff
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
     * Set staffAttribute.
     *
     * @param string $staffAttribute
     *
     * @return SelfDeliveryStaff
     */
    public function setStaffAttribute($staffAttribute)
    {
        $this->staff_attribute = $staffAttribute;

        return $this;
    }

    /**
     * Get staffAttribute.
     *
     * @return string
     */
    public function getStaffAttribute()
    {
        return $this->staff_attribute;
    }

    /**
     * Set staffNo.
     *
     * @param string|null $staffNo
     *
     * @return SelfDeliveryStaff
     */
    public function setStaffNo($staffNo = null)
    {
        $this->staff_no = $staffNo;

        return $this;
    }

    /**
     * Get staffNo.
     *
     * @return string|null
     */
    public function getStaffNo()
    {
        return $this->staff_no;
    }

    /**
     * Set staffType.
     *
     * @param string $staffType
     *
     * @return SelfDeliveryStaff
     */
    public function setStaffType($staffType)
    {
        $this->staff_type = $staffType;

        return $this;
    }

    /**
     * Get staffType.
     *
     * @return string
     */
    public function getStaffType()
    {
        return $this->staff_type;
    }

    /**
     * Set paymentMethod.
     *
     * @param string $paymentMethod
     *
     * @return SelfDeliveryStaff
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->payment_method = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod.
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * Set paymentFee.
     *
     * @param int $paymentFee
     *
     * @return SelfDeliveryStaff
     */
    public function setPaymentFee($paymentFee)
    {
        $this->payment_fee = $paymentFee;

        return $this;
    }

    /**
     * Get paymentFee.
     *
     * @return int
     */
    public function getPaymentFee()
    {
        return $this->payment_fee;
    }


    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SelfDeliveryStaff
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
     * @param int $updated
     *
     * @return SelfDeliveryStaff
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set distributorId.
     *
     * @param string|null $distributorId
     *
     * @return SelfDeliveryStaff
     */
    public function setDistributorId($distributorId = null)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return string|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set shopId.
     *
     * @param string|null $shopId
     *
     * @return SelfDeliveryStaff
     */
    public function setShopId($shopId = null)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId.
     *
     * @return string|null
     */
    public function getShopId()
    {
        return $this->shop_id;
    }
}
