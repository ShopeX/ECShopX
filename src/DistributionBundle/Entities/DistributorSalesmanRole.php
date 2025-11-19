<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
