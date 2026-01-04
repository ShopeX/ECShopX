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

namespace DataCubeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Sources 小程序来源列表
 *
 * @ORM\Table(name="sources", options={"comment":"小程序来源列表"})
 * @ORM\Entity(repositoryClass="DataCubeBundle\Repositories\SourcesRepository")
 */
class Sources
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="source_id", type="bigint", options={"comment":"来源id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $source_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="source_name", type="string", options={"comment":"来源名称"})
     */
    private $source_name;

    /**
     * @var string
     *
     * @ORM\Column(name="tags_id", type="text", nullable=true, options={"comment":"会员标签"})
     */
    private $tags_id;

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
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Sources
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
     * Set sourceName
     *
     * @param string $sourceName
     *
     * @return Sources
     */
    public function setSourceName($sourceName)
    {
        $this->source_name = $sourceName;

        return $this;
    }

    /**
     * Get sourceName
     *
     * @return string
     */
    public function getSourceName()
    {
        return $this->source_name;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Sources
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
     * @return Sources
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
     * Set tagsId
     *
     * @param string $tagsId
     *
     * @return Sources
     */
    public function setTagsId($tagsId)
    {
        $this->tags_id = $tagsId;

        return $this;
    }

    /**
     * Get tagsId
     *
     * @return string
     */
    public function getTagsId()
    {
        return $this->tags_id;
    }
}
