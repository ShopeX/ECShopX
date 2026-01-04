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

/**
 * ResourcesOpLog 资源操作记录
 *
 * @ORM\Table(name="resources_op_log", options={"comment":"资源操作记录表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\ResourcesOpLogRepository")
 */
class ResourcesOpLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"记录id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="resource_id", type="bigint", options={"comment":"资源包id"})
     *
     */
    private $resource_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"门店id", "default":0})
     *
     */
    private $shop_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="store_name", type="string", length=100, options={"comment":"门店名称"})
     *
     */
    private $store_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="op_time", type="integer", length=100, options={"comment":"操作时间"})
     *
     */
    private $op_time;

    /**
     * @var string
     *
     * @ORM\Column(name="op_type", type="string", length=100, options={"comment":"操作类型。occupy:占用资源, release:释放资源"})
     *
     */
    private $op_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="op_num", type="integer", length=100, options={"comment":"操作数量"})
     *
     */
    private $op_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"操作员id"})
     *
     */
    private $operator_id;

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
     * @return ResourcesOpLog
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
     * Set resourceId
     *
     * @param integer $resourceId
     *
     * @return ResourcesOpLog
     */
    public function setResourceId($resourceId)
    {
        $this->resource_id = $resourceId;

        return $this;
    }

    /**
     * Get resourceId
     *
     * @return integer
     */
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return ResourcesOpLog
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
     * Set storeName
     *
     * @param string $storeName
     *
     * @return ResourcesOpLog
     */
    public function setStoreName($storeName)
    {
        $this->store_name = $storeName;

        return $this;
    }

    /**
     * Get storeName
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->store_name;
    }

    /**
     * Set opTime
     *
     * @param integer $opTime
     *
     * @return ResourcesOpLog
     */
    public function setOpTime($opTime)
    {
        $this->op_time = $opTime;

        return $this;
    }

    /**
     * Get opTime
     *
     * @return integer
     */
    public function getOpTime()
    {
        return $this->op_time;
    }

    /**
     * Set opType
     *
     * @param string $opType
     *
     * @return ResourcesOpLog
     */
    public function setOpType($opType)
    {
        $this->op_type = $opType;

        return $this;
    }

    /**
     * Get opType
     *
     * @return string
     */
    public function getOpType()
    {
        return $this->op_type;
    }

    /**
     * Set opNum
     *
     * @param integer $opNum
     *
     * @return ResourcesOpLog
     */
    public function setOpNum($opNum)
    {
        $this->op_num = $opNum;

        return $this;
    }

    /**
     * Get opNum
     *
     * @return integer
     */
    public function getOpNum()
    {
        return $this->op_num;
    }

    /**
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return ResourcesOpLog
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
}
