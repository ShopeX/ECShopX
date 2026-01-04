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
 * RefundErrorLogs 退款错误日志
 *
 * @ORM\Table(name="refund_error_logs", options={"comment":"退款错误日志"},
 *     indexes={
 *         @ORM\Index(name="idx_company", columns={"company_id"}),
 *         @ORM\Index(name="idx_merchant_id", columns={"merchant_id"}),
 *         @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\RefundErrorLogsRepository")
 */
class RefundErrorLogs
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
     *
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商id", "default":0})
     */
    private $supplier_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", type="string", nullable=true, options={"comment":"小程序appid"})
     */
    private $wxa_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="data_json", type="text", nullable=true, options={"comment":"data数据json格式"})
     */
    private $data_json;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=true, options={"comment":"错误状态"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_code", type="string", length=500, nullable=true, options={"comment":"错误码"})
     */
    private $error_code;

    /**
     * @var string
     *
     * @ORM\Column(name="error_desc", type="text", nullable=true, options={"comment":"错误描述"})
     */
    private $error_desc;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_resubmit", type="boolean", nullable=true, options={"comment":"是否重新提交", "default": false})
     */
    private $is_resubmit = false;


    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"订单创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"订单更新时间"})
     */
    private $update_time;
    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RefundErrorLogs
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        // 0x456353686f7058
        return $this->company_id;
    }

    /**
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return RefundErrorLogs
     */
    public function setWxaAppid($wxaAppid)
    {
        // 0x456353686f7058
        $this->wxa_appid = $wxaAppid;

        return $this;
    }

    /**
     * Get wxaAppid
     *
     * @return string
     */
    public function getWxaAppid()
    {
        return $this->wxa_appid;
    }

    /**
     * Set dataJson
     *
     * @param string $dataJson
     *
     * @return RefundErrorLogs
     */
    public function setDataJson($dataJson)
    {
        $this->data_json = $dataJson;

        return $this;
    }

    /**
     * Get dataJson
     *
     * @return string
     */
    public function getDataJson()
    {
        return $this->data_json;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return RefundErrorLogs
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return RefundErrorLogs
     */
    public function setErrorCode($errorCode)
    {
        $this->error_code = $errorCode;

        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * Set errorDesc
     *
     * @param string $errorDesc
     *
     * @return RefundErrorLogs
     */
    public function setErrorDesc($errorDesc)
    {
        $this->error_desc = $errorDesc;

        return $this;
    }

    /**
     * Get errorDesc
     *
     * @return string
     */
    public function getErrorDesc()
    {
        return $this->error_desc;
    }

    /**
     * Set isResubmit
     *
     * @param boolean $isResubmit
     *
     * @return RefundErrorLogs
     */
    public function setIsResubmit($isResubmit)
    {
        $this->is_resubmit = $isResubmit;

        return $this;
    }

    /**
     * Get isResubmit
     *
     * @return boolean
     */
    public function getIsResubmit()
    {
        return $this->is_resubmit;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return RefundErrorLogs
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return integer
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime
     *
     * @param integer $updateTime
     *
     * @return RefundErrorLogs
     */
    public function setUpdateTime($updateTime)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return integer
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return RefundErrorLogs
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return RefundErrorLogs
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return RefundErrorLogs
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
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return RefundErrorLogs
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
