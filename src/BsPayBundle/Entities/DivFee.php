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

namespace BsPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * DivFee 分账金额表
 *
 * @ORM\Table(name="bspay_div_fee", options={"comment":"分账金额表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="ix_supplier_id", columns={"supplier_id"}),
 *    @ORM\Index(name="ix_order_id", columns={"order_id"}),
 *    @ORM\Index(name="ix_trade_id", columns={"trade_id"}),
 *    @ORM\Index(name="ix_list", columns={"company_id","huifu_id"}),
 *    @ORM\Index(name="ix_merchant", columns={"merchant_id"})
 * })
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\DivFeeRepository")
 */
class DivFee
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
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商ID", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * @var string
     *
     *  distributor  店铺
     *  dealer  经销
     *  admin  超级管理员
     *  supplier 经销商
     * 
     * @ORM\Column(name="operator_type", type="string", options={"comment":"操作者类型:distributor-店铺;dealer-经销;admin:超级管理员;supplier:经销商"})
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
     * @var string
     *
     * @ORM\Column(name="huifu_id", nullable=true, type="string", options={"comment":"汇付ID", "default": ""})
     */
    private $huifu_id;

    /**
     * @var string
     *
     * @ORM\Column(name="merchant_id", type="string", nullable=true, options={"comment":"商户ID，记录分账对象是商户时的商户ID"})
     */
    private $merchant_id;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $updated;

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
     * @return DivFee
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
     * @return DivFee
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
     * @return DivFee
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
     * @return DivFee
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
     * Set operatorType.
     *
     * @param string $operatorType
     *
     * @return DivFee
     */
    public function setOperatorType($operatorType)
    {
        $this->operator_type = $operatorType;

        return $this;
    }

    /**
     * Get operatorType.
     *
     * @return string
     */
    public function getOperatorType()
    {
        return $this->operator_type;
    }

    /**
     * Set payFee.
     *
     * @param int $payFee
     *
     * @return DivFee
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
     * Set divFee.
     *
     * @param int|null $divFee
     *
     * @return DivFee
     */
    public function setDivFee($divFee = null)
    {
        $this->div_fee = $divFee;

        return $this;
    }

    /**
     * Get divFee.
     *
     * @return int|null
     */
    public function getDivFee()
    {
        return $this->div_fee;
    }

    /**
     * Set huifuId.
     *
     * @param string|null $huifuId
     *
     * @return DivFee
     */
    public function setHuifuId($huifuId = null)
    {
        $this->huifu_id = $huifuId;

        return $this;
    }

    /**
     * Get huifuId.
     *
     * @return string|null
     */
    public function getHuifuId()
    {
        return $this->huifu_id;
    }

    /**
     * Set merchantId.
     *
     * @param string|null $merchantId
     *
     * @return DivFee
     */
    public function setMerchantId($merchantId = null)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return string|null
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return DivFee
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
     * @return DivFee
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
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return DivFee
     */
    public function setSupplierId($supplierId)
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
