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
 * TemplateOrders 小程序开通表
 *
 * @ORM\Table(name="template_orders", options={"comment":"小程序开通表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\TemplateOrdersRepository")
 */
class TemplateOrders
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="template_orders_id", type="bigint", options={"comment":"小程序模版开通ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $template_orders_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"comment":"operator_id"})
     */
    private $operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="template_name", type="string", options={"comment":"模版名称"})
     */
    private $template_name;

    /**
     * @var string
     *
     * @ORM\Column(name="total_fee", type="string", options={"comment":"购买模版金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="order_status", type="string", options={"comment":"订单状态。可选值有 DONE—订单完成；NOTPAY—未支付；CANCEL—已取消"})
     */
    private $order_status;

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
     * Get templateOrdersId
     *
     * @return integer
     */
    public function getTemplateOrdersId()
    {
        return $this->template_orders_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return TemplateOrders
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
        return $this->company_id;
    }

    /**
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return TemplateOrders
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return integer
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set templateName
     *
     * @param string $templateName
     *
     * @return TemplateOrders
     */
    public function setTemplateName($templateName)
    {
        $this->template_name = $templateName;

        return $this;
    }

    /**
     * Get templateName
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * Set totalFee
     *
     * @param string $totalFee
     *
     * @return TemplateOrders
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee
     *
     * @return string
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set orderStatus
     *
     * @param string $orderStatus
     *
     * @return TemplateOrders
     */
    public function setOrderStatus($orderStatus)
    {
        $this->order_status = $orderStatus;

        return $this;
    }

    /**
     * Get orderStatus
     *
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return TemplateOrders
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
     * @return TemplateOrders
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
}
