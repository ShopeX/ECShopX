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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * DistributorTags 店铺标签库表
 *
 * @ORM\Table(name="distributor_tags", options={"comment"="店铺标签库表"})
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributorTagsRepository")
 */
class DistributorTags
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="tag_id", type="bigint", options={"comment"="标签id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $tag_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_name", type="string", length=50, options={"comment"="标签名称"})
     */
    private $tag_name;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_color", type="string", length=50, options={"comment"="标签颜色"})
     */
    private $tag_color = '#ff1939';

    /**
     * @var string
     *
     * @ORM\Column(name="font_color", type="string", length=50, options={"comment"="字体颜色"})
     */
    private $font_color = "#ffffff";

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true, options={"comment"="标签描述"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_icon", type="text", nullable=true, options={"comment"="标签icon"})
     */
    private $tag_icon;

    /**
     * @var integer
     *
     * @ORM\Column(name="front_show", nullable=true, type="smallint", options={"comment":"前台是否显示 0 否 1 是", "default": 0})
     */
    private $front_show;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * Get tagId
     *
     * @return integer
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return DistributorTags
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
     * Set tagName
     *
     * @param string $tagName
     *
     * @return DistributorTags
     */
    public function setTagName($tagName)
    {
        $this->tag_name = $tagName;

        return $this;
    }

    /**
     * Get tagName
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->tag_name;
    }

    /**
     * Set tagColor
     *
     * @param string $tagColor
     *
     * @return DistributorTags
     */
    public function setTagColor($tagColor)
    {
        $this->tag_color = $tagColor;

        return $this;
    }

    /**
     * Get tagColor
     *
     * @return string
     */
    public function getTagColor()
    {
        return $this->tag_color;
    }

    /**
     * Set fontColor
     *
     * @param string $fontColor
     *
     * @return DistributorTags
     */
    public function setFontColor($fontColor)
    {
        $this->font_color = $fontColor;

        return $this;
    }

    /**
     * Get fontColor
     *
     * @return string
     */
    public function getFontColor()
    {
        return $this->font_color;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return DistributorTags
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set tagIcon
     *
     * @param string $tagIcon
     *
     * @return DistributorTags
     */
    public function setTagIcon($tagIcon)
    {
        $this->tag_icon = $tagIcon;

        return $this;
    }

    /**
     * Get tagIcon
     *
     * @return string
     */
    public function getTagIcon()
    {
        return $this->tag_icon;
    }

    /**
     * Set frontShow
     *
     * @param integer $frontShow
     *
     * @return DistributorTags
     */
    public function setFrontShow($frontShow)
    {
        $this->front_show = $frontShow;

        return $this;
    }

    /**
     * Get frontShow
     *
     * @return integer
     */
    public function getFrontShow()
    {
        return $this->front_show;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return DistributorTags
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
     * @return DistributorTags
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
