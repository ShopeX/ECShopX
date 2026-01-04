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
 * AdapayPaymentReverse adapay支付撤销表
 *
 * @ORM\Table(name="adapay_payment_reverse", options={"comment":"adapay支付撤销表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_order_no", columns={"order_no"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayPaymentReverseRepository")
 */
class AdapayPaymentReverse
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
     * @var string
     *
     * @ORM\Column(name="payment_id", type="string", options={"comment":"Adapay生成的支付对象id 对应order表的transaction_id"})
     */
    private $payment_id;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_reverse_id", type="string", nullable=true, options={"comment":"支付撤销id"})
     */
    private $payment_reverse_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", options={"comment":"应用app_id"})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_no", type="string", options={"comment":"支付撤销请求订单号"})
     */
    private $order_no;

    /**
     * @var string
     *
     * @ORM\Column(name="reverse_amt", type="string", options={"comment":"撤销金额 单位：元"})
     */
    private $reverse_amt;


    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"状态"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="request_params", type="text", options={"comment":"请求参数 json"})
     */
    private $request_params;

    /**
     * @var string
     *
     * @ORM\Column(name="response_params", type="text", nullable=true, options={"comment":"响应参数 json"})
     */
    private $response_params;

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
     * @return AdapayPaymentReverse
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
     * @return AdapayPaymentReverse
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
     * Set paymentId.
     *
     * @param string $paymentId
     *
     * @return AdapayPaymentReverse
     */
    public function setPaymentId($paymentId)
    {
        $this->payment_id = $paymentId;

        return $this;
    }

    /**
     * Get paymentId.
     *
     * @return string
     */
    public function getPaymentId()
    {
        return $this->payment_id;
    }

    /**
     * Set appId.
     *
     * @param string $appId
     *
     * @return AdapayPaymentReverse
     */
    public function setAppId($appId)
    {
        $this->app_id = $appId;

        return $this;
    }

    /**
     * Get appId.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * Set orderNo.
     *
     * @param string $orderNo
     *
     * @return AdapayPaymentReverse
     */
    public function setOrderNo($orderNo)
    {
        $this->order_no = $orderNo;

        return $this;
    }

    /**
     * Get orderNo.
     *
     * @return string
     */
    public function getOrderNo()
    {
        return $this->order_no;
    }

    /**
     * Set reverseAmt.
     *
     * @param string $reverseAmt
     *
     * @return AdapayPaymentReverse
     */
    public function setReverseAmt($reverseAmt)
    {
        $this->reverse_amt = $reverseAmt;

        return $this;
    }

    /**
     * Get reverseAmt.
     *
     * @return string
     */
    public function getReverseAmt()
    {
        return $this->reverse_amt;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return AdapayPaymentReverse
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set requestParams.
     *
     * @param string $requestParams
     *
     * @return AdapayPaymentReverse
     */
    public function setRequestParams($requestParams)
    {
        $this->request_params = $requestParams;

        return $this;
    }

    /**
     * Get requestParams.
     *
     * @return string
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
     * @return AdapayPaymentReverse
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
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayPaymentReverse
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
     * @return AdapayPaymentReverse
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
     * Set paymentReverseId.
     *
     * @param string|null $paymentReverseId
     *
     * @return AdapayPaymentReverse
     */
    public function setPaymentReverseId($paymentReverseId = null)
    {
        $this->payment_reverse_id = $paymentReverseId;

        return $this;
    }

    /**
     * Get paymentReverseId.
     *
     * @return string|null
     */
    public function getPaymentReverseId()
    {
        return $this->payment_reverse_id;
    }
}
