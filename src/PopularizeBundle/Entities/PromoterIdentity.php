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

namespace PopularizeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PromoterIdentity 推广员身份表
 *
 * @ORM\Table(name="popularize_promoter_identity", options={"comment":"推广员身份表"},indexes={
 *     @ORM\Index(name="idx_companyid", columns={"company_id"}),
 *     @ORM\Index(name="idx_is_subordinates", columns={"is_subordinates"}),
 * })
 * @ORM\Entity(repositoryClass="PopularizeBundle\Repositories\PromoterIdentityRepository")
 */
class PromoterIdentity
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
     * @var string
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", nullable=true, type="string", options={"comment":"推广员身份名称"})
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_subordinates", type="integer", length=4, options={"comment":"是否可发展下级分销员"})
     */
    private $is_subordinates;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_default", type="integer", length=4, options={"comment":"是否为默认","default:0"})
     */
    private $is_default;

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
     * @return PromoterIdentity
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
     * Set name.
     *
     * @param string|null $name
     *
     * @return PromoterIdentity
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set isSubordinates.
     *
     * @param int $isSubordinates
     *
     * @return PromoterIdentity
     */
    public function setIsSubordinates($isSubordinates)
    {
        $this->is_subordinates = $isSubordinates;

        return $this;
    }

    /**
     * Get isSubordinates.
     *
     * @return int
     */
    public function getIsSubordinates()
    {
        return $this->is_subordinates;
    }

    /**
     * Set isDefault.
     *
     * @param int $isDefault
     *
     * @return PromoterIdentity
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault.
     *
     * @return int
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return PromoterIdentity
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
     * @return PromoterIdentity
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
