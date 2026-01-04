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
 * Regionauth 地区权限
 *
 * @ORM\Table(name="companys_regionauth", options={"comment":"地区权限"}, indexes={
 *    @ORM\Index(name="ix_regionauth_id", columns={"regionauth_id"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\RegionauthRepository")
 */
class Regionauth
{
    /**
     * @var integer
     *
     * @ORM\Column(name="regionauth_id", type="bigint", options={"comment":"地区id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $regionauth_id;

    /**
     * @var string
     *
     * @ORM\Column(name="regionauth_name", type="string", nullable=true, length=50, options={"comment":"地区名称"})
     */
    private $regionauth_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer", options={"comment":"数据状态(1正常，-1删除)"})
     */
    private $state;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

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
     * Get regionauthId.
     *
     * @return int
     */
    public function getRegionauthId()
    {
        return $this->regionauth_id;
    }

    /**
     * Set regionauthName.
     *
     * @param string|null $regionauthName
     *
     * @return Regionauth
     */
    public function setRegionauthName($regionauthName = null)
    {
        $this->regionauth_name = $regionauthName;

        return $this;
    }

    /**
     * Get regionauthName.
     *
     * @return string|null
     */
    public function getRegionauthName()
    {
        return $this->regionauth_name;
    }

    /**
     * Set state.
     *
     * @param int $state
     *
     * @return Regionauth
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Regionauth
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
     * Set created.
     *
     * @param int $created
     *
     * @return Regionauth
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
     * @return Regionauth
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
