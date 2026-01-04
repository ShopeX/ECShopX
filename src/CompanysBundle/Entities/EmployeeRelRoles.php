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
 * EmployeeRelRoles  员工绑定角色表
 *
 * @ORM\Table(name="companys_employee_rel_roles", options={"comment":"员工绑定角色表"}, indexes={@ORM\Index(name="idx_operator_id", columns={"operator_id"})}, indexes={@ORM\Index(name="idx_company_id", columns={"company_id"})})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\EmployeeRelRolesRepository")
 */
class EmployeeRelRoles
{
    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     * @ORM\Id
     *
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="role_id", type="string", length=32, options={"comment":"角色id"})
     * @ORM\Id
     */
    private $role_id;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_id", type="string", length=32, options={"comment":"员工id"})
     * @ORM\Id
     */
    private $operator_id;

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return EmployeeRelRoles
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
     * Set roleId
     *
     * @param string $roleId
     *
     * @return EmployeeRelRoles
     */
    public function setRoleId($roleId)
    {
        $this->role_id = $roleId;

        return $this;
    }

    /**
     * Get roleId
     *
     * @return string
     */
    public function getRoleId()
    {
        return $this->role_id;
    }

    /**
     * Set operatorId
     *
     * @param string $operatorId
     *
     * @return EmployeeRelRoles
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return string
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }
}
