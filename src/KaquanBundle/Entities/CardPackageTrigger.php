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

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CardPackageTrigger(卡券包触发关联表)
 *
 * @ORM\Table(name="card_package_trigger", options={"comment":"卡券包触发关联表"},indexes={
 *  @ORM\Index(name="idx_companyid", columns={"company_id"}),
 *  @ORM\Index(name="idx_type", columns={"trigger_type"})
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\CardPackageTriggerRepository")
 */
class CardPackageTrigger
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"主键ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="package_id", type="bigint", nullable=false, options={"comment":"卡券包ID"})
     */
    private $package_id;

    /**
     * @var integer
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="trigger_type", type="string", length=15, nullable=false, options={"comment":"触发类型"})
     */
    private $trigger_type;

    /**
     * @var integer
     * @ORM\Column(name="association_id", type="bigint", nullable=false, options={"comment":"关联ID"})
     */
    private $association_id;

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
     * @ORM\Column(type="integer")
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
     * Set packageId.
     *
     * @param int $packageId
     *
     * @return CardPackageTrigger
     */
    public function setPackageId($packageId)
    {
        $this->package_id = $packageId;

        return $this;
    }

    /**
     * Get packageId.
     *
     * @return int
     */
    public function getPackageId()
    {
        return $this->package_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return CardPackageTrigger
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
     * Set triggerType.
     *
     * @param string $triggerType
     *
     * @return CardPackageTrigger
     */
    public function setTriggerType($triggerType)
    {
        $this->trigger_type = $triggerType;

        return $this;
    }

    /**
     * Get triggerType.
     *
     * @return string
     */
    public function getTriggerType()
    {
        return $this->trigger_type;
    }

    /**
     * Set associationId.
     *
     * @param int $associationId
     *
     * @return CardPackageTrigger
     */
    public function setAssociationId($associationId)
    {
        $this->association_id = $associationId;

        return $this;
    }

    /**
     * Get associationId.
     *
     * @return int
     */
    public function getAssociationId()
    {
        return $this->association_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return CardPackageTrigger
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
     * @param int $updated
     *
     * @return CardPackageTrigger
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
