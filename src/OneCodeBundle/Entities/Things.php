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

namespace OneCodeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Things
 *
 * @ORM\Table(name="onecode_things", options={"comment"="物品表，一物一码的物"})
 * @ORM\Entity(repositoryClass="OneCodeBundle\Repositories\ThingsRepository")
 */
class Things
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="thing_id", type="bigint", options={"comment":"物品ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $thing_id;

    /**
     * @var string
     *
     * @ORM\Column(name="thing_name", type="string", length=255, options={"comment":"物品名称"})
     */
    private $thing_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"官方建议售价,单位为‘分’"})
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="pic", type="string", options={"comment":"图片"})
     */
    private $pic;

    /**
     * @var string
     *
     * @ORM\Column(name="intro", type="text", nullable=true, options={"comment":"图文详情"})
     */
    private $intro;

    /**
     * @var integer
     *
     * @ORM\Column(name="batch_total_count", type="integer", options={"comment":"总批次数"})
     */
    private $batch_total_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="batch_total_quantity", type="integer", options={"comment":"总件数"})
     */
    private $batch_total_quantity = 0;


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
     * Get thingId
     *
     * @return integer
     */
    public function getThingId()
    {
        return $this->thing_id;
    }

    /**
     * Set thingName
     *
     * @param string $thingName
     *
     * @return Things
     */
    public function setThingName($thingName)
    {
        $this->thing_name = $thingName;

        return $this;
    }

    /**
     * Get thingName
     *
     * @return string
     */
    public function getThingName()
    {
        return $this->thing_name;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Things
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
     * Set price
     *
     * @param integer $price
     *
     * @return Things
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set pic
     *
     * @param string $pic
     *
     * @return Things
     */
    public function setPic($pic)
    {
        $this->pic = $pic;

        return $this;
    }

    /**
     * Get pic
     *
     * @return string
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * Set intro
     *
     * @param string $intro
     *
     * @return Things
     */
    public function setIntro($intro)
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Get intro
     *
     * @return string
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Things
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
     * @return Things
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

    /**
     * Set batchTotalCount
     *
     * @param integer $batchTotalCount
     *
     * @return Things
     */
    public function setBatchTotalCount($batchTotalCount)
    {
        $this->batch_total_count = $batchTotalCount;

        return $this;
    }

    /**
     * Get batchTotalCount
     *
     * @return integer
     */
    public function getBatchTotalCount()
    {
        return $this->batch_total_count;
    }

    /**
     * Set batchTotalQuantity
     *
     * @param integer $batchTotalQuantity
     *
     * @return Things
     */
    public function setBatchTotalQuantity($batchTotalQuantity)
    {
        $this->batch_total_quantity = $batchTotalQuantity;

        return $this;
    }

    /**
     * Get batchTotalQuantity
     *
     * @return integer
     */
    public function getBatchTotalQuantity()
    {
        return $this->batch_total_quantity;
    }
}
