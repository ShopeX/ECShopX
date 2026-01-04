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

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrdersRelActivity 内购订单关联活动企业表
 *
 * @ORM\Table(name="employee_purchase_orders_rel_activity", options={"comment"="内购订单关联活动企业表"},
 *     indexes={
 *         @ORM\Index(name="idx_enterprise_id", columns={"enterprise_id"}),
 *         @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\OrdersRelActivityRepository")
 */
class OrdersRelActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="order_id", type="bigint", options={"comment":"购物车ID"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业id"})
     */
    private $enterprise_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id",type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户ID"})
     */
    private $user_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="if_share_store", type="boolean", options={"comment":"是否共享库存", "default":false})
     */
    private $if_share_store = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="close_modify_time", type="integer", options={"comment":"订单关闭修改时间"})
     */
    private $close_modify_time;

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return OrdersRelActivity
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return OrdersRelActivity
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
     * Set enterpriseId.
     *
     * @param int $enterpriseId
     *
     * @return OrdersRelActivity
     */
    public function setEnterpriseId($enterpriseId)
    {
        $this->enterprise_id = $enterpriseId;

        return $this;
    }

    /**
     * Get enterpriseId.
     *
     * @return int
     */
    public function getEnterpriseId()
    {
        return $this->enterprise_id;
    }

    /**
     * Set activityId
     *
     * @param integer $activityId
     *
     * @return OrdersRelActivity
     */
    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId
     *
     * @return integer
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return OrdersRelActivity
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set ifShareStore.
     *
     * @param string $ifShareStore
     *
     * @return OrdersRelActivity
     */
    public function setIfShareStore($ifShareStore)
    {
        $this->if_share_store = $ifShareStore;

        return $this;
    }

    /**
     * Get ifShareStore.
     *
     * @return string
     */
    public function getIfShareStore()
    {
        return $this->if_share_store;
    }

    /**
     * Set closeModifyTime.
     *
     * @param string $closeModifyTime
     *
     * @return OrdersRelActivity
     */
    public function setCloseModifyTime($closeModifyTime)
    {
        $this->close_modify_time = $closeModifyTime;

        return $this;
    }

    /**
     * Get closeModifyTime.
     *
     * @return string
     */
    public function getCloseModifyTime()
    {
        return $this->close_modify_time;
    }
}
