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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * DistributorSalesman 店铺导购员表
 *
 * @ORM\Table(name="distribution_distributor_salesman",options={"comment":"店铺导购员表"})
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributorSalesmanRepository")
 */
class DistributorSalesman
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="salesman_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $salesman_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"上线店铺ID"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=32, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="salesman_name", type="string", length=32, options={"comment":"姓名"})
     */
    private $salesman_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="child_count", type="integer", nullable=true, options={"comment":"导购注册人员数量", "default": 0})
     */
    private $child_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", nullable=true, type="string")
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="is_valid", type="string", options={"comment":"是否有效"})
     */
    private $is_valid;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * Get salesmanId
     *
     * @return integer
     */

    public function getSalesmanId()
    {
        // ShopEx EcShopX Service Component
        return $this->salesman_id;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return DistributorSalesman
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return DistributorSalesman
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
     * Set mobile
     *
     * @param string $mobile
     *
     * @return DistributorSalesman
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set userId
     *
     * @param string $userId
     *
     * @return DistributorSalesman
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set isValid
     *
     * @param string $isValid
     *
     * @return DistributorSalesman
     */
    public function setIsValid($isValid)
    {
        $this->is_valid = $isValid;

        return $this;
    }

    /**
     * Get isValid
     *
     * @return string
     */
    public function getIsValid()
    {
        return $this->is_valid;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return DistributorSalesman
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return DistributorSalesman
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set salesmanName
     *
     * @param string $salesmanName
     *
     * @return DistributorSalesman
     */
    public function setSalesmanName($salesmanName)
    {
        $this->salesman_name = $salesmanName;

        return $this;
    }

    /**
     * Get salesmanName
     *
     * @return string
     */
    public function getSalesmanName()
    {
        return $this->salesman_name;
    }

    /**
     * Set childCount
     *
     * @param integer $childCount
     *
     * @return DistributorSalesman
     */
    public function setChildCount($childCount)
    {
        $this->child_count = $childCount;

        return $this;
    }

    /**
     * Get childCount
     *
     * @return integer
     */
    public function getChildCount()
    {
        return $this->child_count;
    }
}
