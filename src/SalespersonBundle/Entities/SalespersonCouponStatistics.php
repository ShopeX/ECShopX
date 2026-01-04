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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * SalespersonCouponStatistics 导购员销售提成数据统计表
 *
 * @ORM\Table(name="salesperson_coupon_statistics", options={"comment":"导购员优惠券数据统计表"}, indexes={
 *    @ORM\Index(name="ix_salesperson_date", columns={"salesperson_id", "date"}),
 * })
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonCouponStatisticsRepository")
 */
class SalespersonCouponStatistics
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     *
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购员id"})
     *
     */
    private $salesperson_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="coupon_id", type="bigint", options={"comment":"优惠券id"})
     *
     */
    private $coupon_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="date", type="bigint", options={"comment":"统计日期 Ymd"})
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="send_num", type="bigint", options={"comment":"赠送张数"})
     */
    private $send_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="pay_num", type="bigint", options={"comment":"支付使用"})
     */
    private $pay_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="receive_num", type="bigint", options={"comment":"领取张数"})
     */
    private $receive_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="reg_num", type="bigint", options={"comment":"分享注册数"})
     */
    private $reg_num;

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
     * @return SalespersonCouponStatistics
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
     * @return SalespersonCouponStatistics
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
     * Set salespersonId.
     *
     * @param int $salespersonId
     *
     * @return SalespersonCouponStatistics
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId.
     *
     * @return int
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set couponId.
     *
     * @param int $couponId
     *
     * @return SalespersonCouponStatistics
     */
    public function setCouponId($couponId)
    {
        $this->coupon_id = $couponId;

        return $this;
    }

    /**
     * Get couponId.
     *
     * @return int
     */
    public function getCouponId()
    {
        return $this->coupon_id;
    }

    /**
     * Set date.
     *
     * @param int $date
     *
     * @return SalespersonCouponStatistics
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set sendNum.
     *
     * @param int $sendNum
     *
     * @return SalespersonCouponStatistics
     */
    public function setSendNum($sendNum)
    {
        $this->send_num = $sendNum;

        return $this;
    }

    /**
     * Get sendNum.
     *
     * @return int
     */
    public function getSendNum()
    {
        return $this->send_num;
    }

    /**
     * Set payNum.
     *
     * @param int $payNum
     *
     * @return SalespersonCouponStatistics
     */
    public function setPayNum($payNum)
    {
        $this->pay_num = $payNum;

        return $this;
    }

    /**
     * Get payNum.
     *
     * @return int
     */
    public function getPayNum()
    {
        return $this->pay_num;
    }

    /**
     * Set receiveNum.
     *
     * @param int $receiveNum
     *
     * @return SalespersonCouponStatistics
     */
    public function setReceiveNum($receiveNum)
    {
        $this->receive_num = $receiveNum;

        return $this;
    }

    /**
     * Get receiveNum.
     *
     * @return int
     */
    public function getReceiveNum()
    {
        return $this->receive_num;
    }

    /**
     * Set regNum.
     *
     * @param int $regNum
     *
     * @return SalespersonCouponStatistics
     */
    public function setRegNum($regNum)
    {
        $this->reg_num = $regNum;

        return $this;
    }

    /**
     * Get regNum.
     *
     * @return int
     */
    public function getRegNum()
    {
        return $this->reg_num;
    }
}
