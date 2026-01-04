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
 * @ORM\Table(name="crossborder_taxstrategy", options={"comment":"跨境-税费策略"}, indexes={
 *    @ORM\Index(name="ix_id", columns={"id"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="CrossBorderBundle\Repositories\CrossborderTaxstrategyRepository")
 */
class Taxstrategy
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
     * @var string
     *
     * @ORM\Column(name="taxstrategy_name", type="string", nullable=true, options={"comment":"规则名称"})
     */
    private $taxstrategy_name;

    /**
     * @var string
     *
     * @ORM\Column(name="taxstrategy_content", type="text", nullable=true, options={"comment":"策略内容"})
     */
    private $taxstrategy_content;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer", options={"comment":"数据状态(1正常，-1删除)"})
     */
    private $state;

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
     * @return Taxstrategy
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
     * Set taxstrategyName.
     *
     * @param string|null $taxstrategyName
     *
     * @return Taxstrategy
     */
    public function setTaxstrategyName($taxstrategyName = null)
    {
        $this->taxstrategy_name = $taxstrategyName;

        return $this;
    }

    /**
     * Get taxstrategyName.
     *
     * @return string|null
     */
    public function getTaxstrategyName()
    {
        return $this->taxstrategy_name;
    }

    /**
     * Set taxstrategyContent.
     *
     * @param string|null $taxstrategyContent
     *
     * @return Taxstrategy
     */
    public function setTaxstrategyContent($taxstrategyContent = null)
    {
        $this->taxstrategy_content = $taxstrategyContent;

        return $this;
    }

    /**
     * Get taxstrategyContent.
     *
     * @return string|null
     */
    public function getTaxstrategyContent()
    {
        return $this->taxstrategy_content;
    }

    /**
     * Set state.
     *
     * @param int $state
     *
     * @return Taxstrategy
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
     * Set created.
     *
     * @param int $created
     *
     * @return Taxstrategy
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
     * @return Taxstrategy
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
