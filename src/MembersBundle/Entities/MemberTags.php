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

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MemberTags 会员标签库表
 *
 * @ORM\Table(name="members_tags", options={"comment"="会员标签库表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_source",     columns={"source"}),
 *    @ORM\Index(name="idx_tag_name",     columns={"tag_name"}),
 * }),
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberTagsRepository")
 */
class MemberTags
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
     * @var string
     *
     * @ORM\Column(name="saleman_id", type="integer", options={"comment"="自定义标签添加人员id", "default": 0})
     */
    private $saleman_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_status", type="string", length=32, options={"comment"="标签类型，online：线上发布, self: 私有自定义", "default": "online"})
     */
    private $tag_status = 'online';

    /**
     * @var string
     *
     * @ORM\Column(name="category_id", type="integer", options={"comment"="分类id", "default": 0})
     */
    private $category_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="self_tag_count", type="integer", options={"comment"="自定义标签下会员数量", "default": 0})
     */
    private $self_tag_count = 0;

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
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

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
     * @var string
     *
     * @ORM\Column(name="source", type="string", options={"comment":"标签来源,self:商户自定义，staff:系统固定员工tag","default":"self"})
     */
    private $source = 'self';

    /**
     * Get tagId.
     *
     * @return int
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return MemberTags
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
     * Set tagName.
     *
     * @param string $tagName
     *
     * @return MemberTags
     */
    public function setTagName($tagName)
    {
        $this->tag_name = $tagName;

        return $this;
    }

    /**
     * Get tagName.
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->tag_name;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return MemberTags
     */
    public function setDescription($description = null)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set tagIcon.
     *
     * @param string|null $tagIcon
     *
     * @return MemberTags
     */
    public function setTagIcon($tagIcon = null)
    {
        $this->tag_icon = $tagIcon;

        return $this;
    }

    /**
     * Get tagIcon.
     *
     * @return string|null
     */
    public function getTagIcon()
    {
        return $this->tag_icon;
    }

    /**
     * Set salemanId.
     *
     * @param int $salemanId
     *
     * @return MemberTags
     */
    public function setSalemanId($salemanId)
    {
        $this->saleman_id = $salemanId;

        return $this;
    }

    /**
     * Get salemanId.
     *
     * @return int
     */
    public function getSalemanId()
    {
        return $this->saleman_id;
    }

    /**
     * Set tagStatus.
     *
     * @param string $tagStatus
     *
     * @return MemberTags
     */
    public function setTagStatus($tagStatus)
    {
        $this->tag_status = $tagStatus;

        return $this;
    }

    /**
     * Get tagStatus.
     *
     * @return string
     */
    public function getTagStatus()
    {
        return $this->tag_status;
    }

    /**
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return MemberTags
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set selfTagCount.
     *
     * @param int $selfTagCount
     *
     * @return MemberTags
     */
    public function setSelfTagCount($selfTagCount)
    {
        $this->self_tag_count = $selfTagCount;

        return $this;
    }

    /**
     * Get selfTagCount.
     *
     * @return int
     */
    public function getSelfTagCount()
    {
        return $this->self_tag_count;
    }

    /**
     * Set tagColor.
     *
     * @param string $tagColor
     *
     * @return MemberTags
     */
    public function setTagColor($tagColor)
    {
        $this->tag_color = $tagColor;

        return $this;
    }

    /**
     * Get tagColor.
     *
     * @return string
     */
    public function getTagColor()
    {
        return $this->tag_color;
    }

    /**
     * Set fontColor.
     *
     * @param string $fontColor
     *
     * @return MemberTags
     */
    public function setFontColor($fontColor)
    {
        $this->font_color = $fontColor;

        return $this;
    }

    /**
     * Get fontColor.
     *
     * @return string
     */
    public function getFontColor()
    {
        return $this->font_color;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return MemberTags
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return MemberTags
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
     * @return MemberTags
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

    /**
     * Set source.
     *
     * @param string $source
     *
     * @return MemberTags
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }
}
