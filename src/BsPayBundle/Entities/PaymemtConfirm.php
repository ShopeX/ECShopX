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
 * PaymemtConfirm 斗拱支付确认表
 *
 * @ORM\Table(name="bspay_paymemt_confirm", options={"comment":"斗拱支付确认表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_order_no", columns={"order_no"}),
 *         @ORM\Index(name="distributor_id", columns={"distributor_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\PaymemtConfirmRepository")
 */
class PaymemtConfirm
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
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", options={"comment":"订单id"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_id", type="string", nullable=true, options={"comment":"斗拱生成的交易订单号 对应order表的transaction_id"})
     */
    private $payment_id;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_confirmation_id", type="string", nullable=true, options={"comment":"支付确认id"})
     */
    private $payment_confirmation_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_no", type="string", nullable=true, options={"comment":"支付确认请求订单号"})
     */
    private $order_no;

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_amt", type="string", nullable=true, options={"comment":"确认金额 单位：元"})
     */
    private $confirm_amt;

    /**
     * @var string
     *
     * @ORM\Column(name="div_members", type="text", nullable=true, options={"comment":"分账对象信息列表 json"})
     */
    private $div_members;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=true, options={"comment":"交易状态"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="request_params", type="text", nullable=true, options={"comment":"请求参数 json"})
     */
    private $request_params;

    /**
     * @var string
     *
     * @ORM\Column(name="response_params", type="text", nullable=true, options={"comment":"响应参数 json"})
     */
    private $response_params;

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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PaymemtConfirm
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
     * Set orderId.
     *
     * @param string $orderId
     *
     * @return PaymemtConfirm
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
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return PaymemtConfirm
     */
    public function setDistributorId($distributorId = null)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set paymentId.
     *
     * @param string|null $paymentId
     *
     * @return PaymemtConfirm
     */
    public function setPaymentId($paymentId = null)
    {
        $this->payment_id = $paymentId;

        return $this;
    }

    /**
     * Get paymentId.
     *
     * @return string|null
     */
    public function getPaymentId()
    {
        return $this->payment_id;
    }

    /**
     * Set paymentConfirmationId.
     *
     * @param string|null $paymentConfirmationId
     *
     * @return PaymemtConfirm
     */
    public function setPaymentConfirmationId($paymentConfirmationId = null)
    {
        $this->payment_confirmation_id = $paymentConfirmationId;

        return $this;
    }

    /**
     * Get paymentConfirmationId.
     *
     * @return string|null
     */
    public function getPaymentConfirmationId()
    {
        return $this->payment_confirmation_id;
    }

    /**
     * Set orderNo.
     *
     * @param string|null $orderNo
     *
     * @return PaymemtConfirm
     */
    public function setOrderNo($orderNo = null)
    {
        $this->order_no = $orderNo;

        return $this;
    }

    /**
     * Get orderNo.
     *
     * @return string|null
     */
    public function getOrderNo()
    {
        return $this->order_no;
    }

    /**
     * Set confirmAmt.
     *
     * @param string|null $confirmAmt
     *
     * @return PaymemtConfirm
     */
    public function setConfirmAmt($confirmAmt = null)
    {
        $this->confirm_amt = $confirmAmt;

        return $this;
    }

    /**
     * Get confirmAmt.
     *
     * @return string|null
     */
    public function getConfirmAmt()
    {
        return $this->confirm_amt;
    }

    /**
     * Set divMembers.
     *
     * @param string|null $divMembers
     *
     * @return PaymemtConfirm
     */
    public function setDivMembers($divMembers = null)
    {
        $this->div_members = $divMembers;

        return $this;
    }

    /**
     * Get divMembers.
     *
     * @return string|null
     */
    public function getDivMembers()
    {
        return $this->div_members;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return PaymemtConfirm
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
     * Set requestParams.
     *
     * @param string|null $requestParams
     *
     * @return PaymemtConfirm
     */
    public function setRequestParams($requestParams = null)
    {
        $this->request_params = $requestParams;

        return $this;
    }

    /**
     * Get requestParams.
     *
     * @return string|null
     */
    public function getRequestParams()
    {
        return $this->request_params;
    }

    /**
     * Set responseParams.
     *
     * @param string|null $responseParams
     *
     * @return PaymemtConfirm
     */
    public function setResponseParams($responseParams = null)
    {
        $this->response_params = $responseParams;

        return $this;
    }

    /**
     * Get responseParams.
     *
     * @return string|null
     */
    public function getResponseParams()
    {
        return $this->response_params;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return PaymemtConfirm
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
     * @return PaymemtConfirm
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
