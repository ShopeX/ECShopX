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

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EnterpriseEmailBox 企业发件邮箱
 *
 * @ORM\Table(name="employee_purchase_enterprise_email_box", options={"comment":"企业发件邮箱"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_enterprise_id", columns={"enterprise_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\EnterpriseEmailBoxRepository")
 */
class EnterpriseEmailBox
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
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业白名单id"})
     */
    private $enterprise_id;

    /**
     * @var string
     *
     * @ORM\Column(name="smtp_port", type="string", length=10, options={"comment":"端口号"})
     */
    private $smtp_port;

    /**
     * @var string
     *
     * @ORM\Column(name="relay_host", type="string", length=50, options={"comment":"服务器主机地址"})
     */
    private $relay_host;

    /**
     * @var string
     *
     * @ORM\Column(name="user", type="string", length=50, options={"comment":"服务器用户名"})
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=50, options={"comment":"服务器密码"})
     */
    private $password;
    
    /**
     * @var string
     *
     * @ORM\Column(name="suffix", type="string", length=50, options={"comment":"员工收件邮箱后缀"})
     */
    private $suffix;

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
     * @return EnterpriseEmailBox
     */
    public function setCompanyId($companyId)
    {
        // Ref: 1996368445
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
     * Set enterpriseId.
     *
     * @param int $enterpriseId
     *
     * @return EnterpriseEmailBox
     */
    public function setEnterpriseId($enterpriseId)
    {
        $this->enterprise_id = $enterpriseId;

        return $this;
    }

    /**
     * Get enterpriseId.
     *
     * @return int
     */
    public function getEnterpriseId()
    {
        // Ref: 1996368445
        return $this->enterprise_id;
    }

    /**
     * Set smtpPort.
     *
     * @param string $smtpPort
     *
     * @return EnterpriseEmailBox
     */
    public function setSmtpPort($smtpPort)
    {
        $this->smtp_port = $smtpPort;

        return $this;
    }

    /**
     * Get smtpPort.
     *
     * @return string
     */
    public function getSmtpPort()
    {
        return $this->smtp_port;
    }

    /**
     * Set relayHost.
     *
     * @param string $relayHost
     *
     * @return EnterpriseEmailBox
     */
    public function setRelayHost($relayHost)
    {
        $this->relay_host = $relayHost;

        return $this;
    }

    /**
     * Get relayHost.
     *
     * @return string
     */
    public function getRelayHost()
    {
        return $this->relay_host;
    }

    /**
     * Set user.
     *
     * @param string $user
     *
     * @return EnterpriseEmailBox
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return EnterpriseEmailBox
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return EnterpriseEmailBox
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return EnterpriseEmailBox
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set suffix.
     *
     * @param string $suffix
     *
     * @return EnterpriseEmailBox
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Get suffix.
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }
}
