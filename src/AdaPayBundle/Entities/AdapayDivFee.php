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

namespace AdaPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AdapayDivFee 分账金额表
 *
 * @ORM\Table(name="adapay_div_fee", options={"comment":"分账金额表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="ix_order_id", columns={"order_id"}),
 *    @ORM\Index(name="ix_trade_id", columns={"trade_id"}),
 *    @ORM\Index(name="ix_list", columns={"company_id","adapay_member_id"}),
 * })
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayDivFeeRepository")
 */
class AdapayDivFee
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
     * @var string
     *
     * @ORM\Column(name="trade_id", type="string", length=64, options={"comment":"交易单号"})
     */
    private $trade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, nullable=true, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="string", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_id", type="string", nullable=true, options={"comment":"店铺ID"})
     */
    private $distributor_id;


    /**
     * @var string
     *
     * @ORM\Column(name="operator_type", type="string", options={"comment":"操作者类型:distributor-店铺;dealer-经销;admin:超级管理员"})
     */
    private $operator_type = "admin";

    /**
     * @var integer
     *
     * @ORM\Column(name="pay_fee", type="integer", options={"comment":"支付金额", "unsigned":true, "default": 0})
     */
    private $pay_fee = 0;

    /**
     * @var integer
     *
     * 分账金额，以分为单位
     *
     * @ORM\Column(name="div_fee", type="integer", nullable=true, options={"unsigned":true, "comment":"当前用户的分账金额，以分为单位","default":0})
     */
    private $div_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="adapay_member_id", type="integer", nullable=true, options={"comment":"汇付账号关联ID", "default": 0})
     */
    private $adapay_member_id = 0;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;


    /**
     * Set id.
     *
     * @param int $id
     *
     * @return AdapayDivFee
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set tradeId.
     *
     * @param string $tradeId
     *
     * @return AdapayDivFee
     */
    public function setTradeId($tradeId)
    {
        $this->trade_id = $tradeId;

        return $this;
    }

    /**
     * Get tradeId.
     *
     * @return string
     */
    public function getTradeId()
    {
        return $this->trade_id;
    }

    /**
     * Set orderId.
     *
     * @param string|null $orderId
     *
     * @return AdapayDivFee
     */
    public function setOrderId($orderId = null)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set companyId.
     *
     * @param string $companyId
     *
     * @return AdapayDivFee
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set distributorId.
     *
     * @param string|null $distributorId
     *
     * @return AdapayDivFee
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
     * Set divFee.
     *
     * @param int $divFee
     *
     * @return AdapayDivFee
     */
    public function setDivFee($divFee)
    {
        $this->div_fee = $divFee;

        return $this;
    }

    /**
     * Get divFee.
     *
     * @return int
     */
    public function getDivFee()
    {
        return $this->div_fee;
    }

    /**
     * Set adapayMemberId.
     *
     * @param int|null $adapayMemberId
     *
     * @return AdapayDivFee
     */
    public function setAdapayMemberId($adapayMemberId = null)
    {
        $this->adapay_member_id = $adapayMemberId;

        return $this;
    }

    /**
     * Get adapayMemberId.
     *
     * @return int|null
     */
    public function getAdapayMemberId()
    {
        return $this->adapay_member_id;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayDivFee
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
     * @return AdapayDivFee
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

    /**
     * Set payFee.
     *
     * @param int $payFee
     *
     * @return AdapayDivFee
     */
    public function setPayFee($payFee)
    {
        $this->pay_fee = $payFee;

        return $this;
    }

    /**
     * Get payFee.
     *
     * @return int
     */
    public function getPayFee()
    {
        return $this->pay_fee;
    }

    /**
     * Set operatorType.
     *
     * @param int|null $operatorType
     *
     * @return AdapayMember
     */
    public function setOperatorType($operatorType = null)
    {
        $this->operator_type = $operatorType;

        return $this;
    }

    /**
     * Get operatorType.
     *
     * @return int|null
     */
    public function getOperatorType()
    {
        return $this->operator_type;
    }
}
