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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * OperatorBindShop 员工关联门店表
 *
 * @ORM\Table(name="operator_bind_shop", options={"comment"="员工关联门店表"},
 *     indexes={
 *         @ORM\Index(name="idx_shop_id", columns={"shop_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\OperatorBindShopRepository")
 */
class OperatorBindShop
{
    use Timestamps;
    use SoftDeletes;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint")
     * @ORM\Id
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="shop_id", type="bigint")
     */
    private $shop_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="bind_status", type="boolean")
     */
    private $bind_status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_shopadmin", type="boolean")
     */
    private $is_shopadmin;

    /**
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return OperatorBindShop
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return OperatorBindShop
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set bindStatus
     *
     * @param boolean $bindStatus
     *
     * @return OperatorBindShop
     */
    public function setBindStatus($bindStatus)
    {
        $this->bind_status = $bindStatus;

        return $this;
    }

    /**
     * Get bindStatus
     *
     * @return boolean
     */
    public function getBindStatus()
    {
        return $this->bind_status;
    }

    /**
     * Set isShopadmin
     *
     * @param boolean $isShopadmin
     *
     * @return OperatorBindShop
     */
    public function setIsShopadmin($isShopadmin)
    {
        $this->is_shopadmin = $isShopadmin;

        return $this;
    }

    /**
     * Get isShopadmin
     *
     * @return boolean
     */
    public function getIsShopadmin()
    {
        return $this->is_shopadmin;
    }
}
