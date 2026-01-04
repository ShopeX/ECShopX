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

/**
 * DistributorSalesmanRole 店铺权限表
 *
 * @ORM\Table(name="distribution_distributor_salesman_role",options={"comment":"店铺权限表"})
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributorSalesmanRoleRepository")
 */
class DistributorSalesmanRole
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="salesman_role_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $salesman_role_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="role_name", type="string", length=32, options={"comment":"导购员角色名称"})
     */
    private $role_name;

    /**
     * @var json_array
     *
     * @ORM\Column(name="rule_ids", nullable=true, type="json_array", options={"comment":"导购员角色类型"})
     */
    private $rule_ids;

    /**
     * Get salesmanRoleId.
     *
     * @return int
     */
    public function getSalesmanRoleId()
    {
        return $this->salesman_role_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return DistributorSalesmanRole
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
     * Set roleName.
     *
     * @param string $roleName
     *
     * @return DistributorSalesmanRole
     */
    public function setRoleName($roleName)
    {
        $this->role_name = $roleName;

        return $this;
    }

    /**
     * Get roleName.
     *
     * @return string
     */
    public function getRoleName()
    {
        return $this->role_name;
    }

    /**
     * Set ruleIds.
     *
     * @param array|null $ruleIds
     *
     * @return DistributorSalesmanRole
     */
    public function setRuleIds($ruleIds = null)
    {
        $this->rule_ids = $ruleIds;

        return $this;
    }

    /**
     * Get ruleIds.
     *
     * @return array|null
     */
    public function getRuleIds()
    {
        return $this->rule_ids;
    }
}
