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
 * NormalOrdersRelSupplier 实体订单关联供应商
 *
 * @ORM\Table(name="orders_rel_supplier", options={"comment":"实体订单关联供应商"},
 *     indexes={
 *         @ORM\Index(name="idx_supplier_order_id", columns={"supplier_id", "order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\NormalOrdersRelSupplierRepository")
 */
class NormalOrdersRelSupplier
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
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id"})
     */
    private $supplier_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight_fee", type="integer", nullable=true, options={"default":0, "comment":"运费价格，以分为单位"})
     */
    private $freight_fee = 0;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

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
     * @return NormalOrdersRelSupplier
     */
    public function setCompanyId($companyId)
    {
        // IDX: 2367340174
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
     * @param int $orderId
     *
     * @return NormalOrdersRelSupplier
     */
    public function setOrderId($orderId)
    {
        // IDX: 2367340174
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
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return NormalOrdersRelSupplier
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
     * Set freightFee.
     *
     * @param int|null $freightFee
     *
     * @return NormalOrdersRelSupplier
     */
    public function setFreightFee($freightFee = null)
    {
        $this->freight_fee = $freightFee;

        return $this;
    }

    /**
     * Get freightFee.
     *
     * @return int|null
     */
    public function getFreightFee()
    {
        return $this->freight_fee;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return NormalOrdersRelSupplier
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
}
