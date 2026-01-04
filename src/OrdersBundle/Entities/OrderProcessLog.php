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
 * OrderProcessLog  订单流程记录表
 *
 * @ORM\Table(name="orders_process_log", options={"comment":"订单流程记录表"},
 *     indexes={
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderProcessLogRepository")
 */
class OrderProcessLog
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
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id", "default":0})
     */
    private $supplier_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_type", type="string", length=20, options={"comment":"操作类型 用户:user 导购:salesperon 管理员:admin 系统:system"})
     */
    private $operator_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", nullable=true, type="bigint", options={"comment":"操作员id", "default": 0})
     */
    private $operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_name", nullable=true, type="string", options={"comment":"操作员名字", "default": ""})
     */
    private $operator_name;

    /**
     * @var string
     *
     * @ORM\Column(name="remarks", type="string", length=30, options={"comment":"订单操作备注"})
     */
    private $remarks;

    /**
     * @var string
     *
     * @ORM\Column(name="detail", type="text", options={"comment":"订单操作detail"})
     */
    private $detail;

    /**
     * @var json_array
     *
     * @ORM\Column(name="params", type="json_array", nullable=true, options={"comment":"提交参数"})
     */
    private $params;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_show", type="boolean", nullable=true, options={"comment":"C端是否可见", "default": false})
     */
    private $is_show = false;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="json_array", nullable=true, options={"comment":"图片记录"})
     */
    private $pics;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_remark", type="string", nullable=true, options={"comment":"订单发货备注"})
     */
    private $delivery_remark;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"订单操作时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"订单操作"})
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
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return OrderProcessLog
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OrderProcessLog
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
     * Set operatorType.
     *
     * @param string $operatorType
     *
     * @return OrderProcessLog
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
     * Set operatorId.
     *
     * @param int|null $operatorId
     *
     * @return OrderProcessLog
     */
    public function setOperatorId($operatorId = null)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int|null
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set operatorName.
     *
     * @param string|null $operatorName
     *
     * @return OrderProcessLog
     */
    public function setOperatorName($operatorName = null)
    {
        if (ismobile($operatorName)) {
            $operatorName = fixedencrypt($operatorName);
        }
        $this->operator_name = $operatorName;

        return $this;
    }

    /**
     * Get operatorName.
     *
     * @return string|null
     */
    public function getOperatorName()
    {
        return fixeddecrypt($this->operator_name);
    }

    /**
     * Set remarks.
     *
     * @param string $remarks
     *
     * @return OrderProcessLog
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks.
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set detail.
     *
     * @param string $detail
     *
     * @return OrderProcessLog
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get detail.
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Set params.
     *
     * @param array|null $params
     *
     * @return OrderProcessLog
     */
    public function setParams($params = null)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params.
     *
     * @return array|null
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return OrderProcessLog
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
     * @return OrderProcessLog
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
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return OrderProcessLog
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

    /**
     * Set isShow.
     *
     * @param bool|null $isShow
     *
     * @return OrderProcessLog
     */
    public function setIsShow($isShow = null)
    {
        $this->is_show = $isShow;

        return $this;
    }

    /**
     * Get isShow.
     *
     * @return bool|null
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * Set pics.
     *
     * @param array|null $pics
     *
     * @return OrderProcessLog
     */
    public function setPics($pics = null)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics.
     *
     * @return array|null
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set deliveryRemark.
     *
     * @param string|null $deliveryRemark
     *
     * @return OrderProcessLog
     */
    public function setDeliveryRemark($deliveryRemark = null)
    {
        $this->delivery_remark = $deliveryRemark;

        return $this;
    }

    /**
     * Get deliveryRemark.
     *
     * @return string|null
     */
    public function getDeliveryRemark()
    {
        return $this->delivery_remark;
    }
}
