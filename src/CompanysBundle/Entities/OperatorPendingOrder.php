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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OperatorPendingOrder 导购挂单
 *
 * @ORM\Table(name="companys_operator_pending_order", options={"comment"="导购挂单"},
 *     indexes={
 *         @ORM\Index(name="idx_operator_id", columns={"operator_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\OperatorPendingOrderRepository")
 */
class OperatorPendingOrder
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="pending_id", type="bigint", options={"comment":"挂单ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $pending_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id",type="bigint", options={"default":0, "comment":"店铺id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"comment":"管理员id"})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"default":0, "comment":"用户id"})
     */
    private $user_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="pending_type", type="string", options={"comment":"挂起类型 cart:收银台 order:订单", "default":"cart"})
     */
    private $pending_type = 'cart';

    /**
     * @var string
     *
     * @ORM\Column(name="pending_data", type="text", options={"comment":"暂存数据"})
     */
    private $pending_data;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    /**
     * Get pendingId
     *
     * @return integer
     */
    public function getPendingId()
    {
        return $this->pending_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return OperatorPendingOrder
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return OperatorPendingOrder
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return OperatorPendingOrder
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return OperatorPendingOrder
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
     * Set pendingType
     *
     * @param string $pendingType
     *
     * @return OperatorPendingOrder
     */
    public function setPendingType($pendingType)
    {
        $this->pending_type = $pendingType;

        return $this;
    }

    /**
     * Get pendingType
     *
     * @return string
     */
    public function getPendingType()
    {
        return $this->pending_type;
    }

    /**
     * Set pendingData
     *
     * @param string $pendingData
     *
     * @return OperatorPendingOrder
     */
    public function setPendingData($pendingData)
    {
        $this->pending_data = $pendingData;

        return $this;
    }

    /**
     * Get pendingData
     *
     * @return string
     */
    public function getPendingData()
    {
        return $this->pending_data;
    }

    /**
     * get Created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * set Created
     *
     * @param \DateTime $created
     *
     * @return OperatorPendingOrder
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * get Updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * set Updated
     *
     * @param \DateTime $updated
     *
     * @return OperatorPendingOrder
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }
}
