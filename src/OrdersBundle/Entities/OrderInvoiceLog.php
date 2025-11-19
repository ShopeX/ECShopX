<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OrderInvoiceLog 订单发票日志表
 *
 * @ORM\Table(name="orders_invoice_log", options={"comment":"订单发票日志表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_invoice_id", columns={"invoice_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_operator_id", columns={"operator_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderInvoiceLogRepository")
 */
class OrderInvoiceLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", length=64, options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="invoice_id", type="bigint", options={"comment":"关联发票表id"})
     */
    private $invoice_id;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_type", type="string", length=20, options={"comment":"操作类型"})
     */
    private $operator_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"操作人id"})
     */
    private $operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_content", type="json_array", nullable=true, options={"comment":"操作内容"})
     */
    private $operator_content;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="create_time", type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="update_time", type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;

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
     * Set invoiceId
     *
     * @param integer $invoiceId
     *
     * @return OrderInvoiceLog
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoice_id = $invoiceId;

        return $this;
    }

    /**
     * Get invoiceId
     *
     * @return integer
     */
    public function getInvoiceId()
    {
        return $this->invoice_id;
    }

    /**
     * Set operatorType
     *
     * @param string $operatorType
     *
     * @return OrderInvoiceLog
     */
    public function setOperatorType($operatorType)
    {
        $this->operator_type = $operatorType;

        return $this;
    }

    /**
     * Get operatorType
     *
     * @return string
     */
    public function getOperatorType()
    {
        return $this->operator_type;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return OrderInvoiceLog
     */
    public function setUserId($userId = null)
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
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return OrderInvoiceLog
     */
    public function setOperatorId($operatorId = null)
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
     * Set operatorContent
     *
     * @param array $operatorContent
     *
     * @return OrderInvoiceLog
     */
    public function setOperatorContent($operatorContent = null)
    {
        $this->operator_content = $operatorContent;

        return $this;
    }

    /**
     * Get operatorContent
     *
     * @return array
     */
    public function getOperatorContent()
    {
        return $this->operator_content;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     *
     * @return OrderInvoiceLog
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return \DateTime
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime
     *
     * @param \DateTime $updateTime
     *
     * @return OrderInvoiceLog
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return \DateTime
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }
}
