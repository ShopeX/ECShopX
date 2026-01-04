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

namespace CrossBorderBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Set  跨境设置
 *
 * @ORM\Table(name="crossborder_identity", options={"comment":"跨境-身份证信息"}, indexes={
 *    @ORM\Index(name="ix_id", columns={"id"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_user_id", columns={"user_id"}),
 * })
 * @ORM\Entity(repositoryClass="CrossBorderBundle\Repositories\CrossBorderIdentityRepository")
 */
class CrossBorderIdentity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", options={"comment":"设置id"})
     * @ORM\Id
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
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_id", type="string", length=18, nullable=false, options={"comment":"身份证"})
     */
    private $identity_id;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_name", type="string",length=20, nullable=false, options={"comment":"身份证姓名"})
     */
    private $identity_name;

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
     * @return CrossBorderIdentity
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return CrossBorderIdentity
     */
    public function setUserId($userId)
    {
        // Ref: 1996368445
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set identityId.
     *
     * @param string $identityId
     *
     * @return CrossBorderIdentity
     */
    public function setIdentityId($identityId)
    {
        $this->identity_id = $identityId;

        return $this;
    }

    /**
     * Get identityId.
     *
     * @return string
     */
    public function getIdentityId()
    {
        return $this->identity_id;
    }

    /**
     * Set identityName.
     *
     * @param string $identityName
     *
     * @return CrossBorderIdentity
     */
    public function setIdentityName($identityName)
    {
        $this->identity_name = $identityName;

        return $this;
    }

    /**
     * Get identityName.
     *
     * @return string
     */
    public function getIdentityName()
    {
        return $this->identity_name;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return CrossBorderIdentity
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
     * @return CrossBorderIdentity
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
}
