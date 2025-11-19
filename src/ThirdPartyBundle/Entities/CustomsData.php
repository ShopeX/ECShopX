<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * queueLog (获取海关数据表)
 *
 * @ORM\Table(name="customs_data", options={"comment"="接收海关支付数据请求表"})
 * @ORM\Entity(repositoryClass="ThirdPartyBundle\Repositories\CustomsDataRepository")
 */
class CustomsData
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", options={"comment":"海关发起请求时，平台接收的会话ID"})
     */
    private $session_id;

    /**
     * @var string
     *
     * @ORM\Column(name="service_time", type="bigint", length=15, options={"comment":"调用时的系统时间"})
     */
    private $service_time;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="boolean", options={"comment":"是否上报 0否 1是", "default": 0})
     */
    private $status = 0;

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return CustomsData
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
     * Set sessionId.
     *
     * @param string $sessionId
     *
     * @return CustomsData
     */
    public function setSessionId($sessionId)
    {
        $this->session_id = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Set serviceTime.
     *
     * @param string $serviceTime
     *
     * @return CustomsData
     */
    public function setServiceTime($serviceTime)
    {
        $this->service_time = $serviceTime;

        return $this;
    }

    /**
     * Get serviceTime.
     *
     * @return string
     */
    public function getServiceTime()
    {
        return $this->service_time;
    }

    /**
     * Set status.
     *
     * @param bool $status
     *
     * @return CustomsData
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }
}
