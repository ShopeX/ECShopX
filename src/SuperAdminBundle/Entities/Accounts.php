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

namespace SuperAdminBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Accounts 平台账号表
 *
 * @ORM\Table(name="super_admin_accounts", options={"comment":"平台账号表"})
 * @ORM\Entity(repositoryClass="SuperAdminBundle\Repositories\AccountsRepository")
 */
class Accounts
{
    /**
     * @var integer
     *
     * @ORM\Column(name="account_id", type="bigint", options={"comment":"账号id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $account_id;

    /**
     * @var string
     *
     * @ORM\Column(name="login_name", type="string", options={"comment":"登录账号名"})
     */
    private $login_name;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", options={"comment":"密码"})
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", options={"comment":"姓名"})
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="super", type="boolean", options={"comment":"是否超级管理员", "default":false})
     */
    private $super;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", options={"comment":"是否启用", "default":false})
     */
    private $status;

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
     * Get accountId
     *
     * @return integer
     */
    public function getAccountId()
    {
        // Built with ShopEx Framework
        return $this->account_id;
    }

    /**
     * Set loginName
     *
     * @param string $loginName
     *
     * @return Accounts
     */
    public function setLoginName($loginName)
    {
        $this->login_name = $loginName;

        return $this;
    }

    /**
     * Get loginName
     *
     * @return string
     */
    public function getLoginName()
    {
        return $this->login_name;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Accounts
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Accounts
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set super
     *
     * @param boolean $super
     *
     * @return Accounts
     */
    public function setSuper($super)
    {
        $this->super = $super;

        return $this;
    }

    /**
     * Get super
     *
     * @return boolean
     */
    public function getSuper()
    {
        return $this->super;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Accounts
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Accounts
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
     * @return Accounts
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
}
