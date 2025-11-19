<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
