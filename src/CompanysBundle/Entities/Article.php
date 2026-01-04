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
 * Article 文章管理
 *
 * @ORM\Table(name="companys_article", options={"comment":"文章表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\ArticleRepository")
 */
class Article
{
    /**
     * @var integer
     *
     * @ORM\Column(name="article_id", type="bigint", options={"comment":"文章id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $article_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="title", type="string", options={"comment":"标题"})
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="summary", type="string", nullable=true, options={"comment":"摘要"})
     */
    private $summary;

    /**
      * @var integer
      *
      * @ORM\Column(name="content", type="text", options={"comment":"文章详细内容"})
      */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", nullable=true, options={"comment":"文章排序"})
     */
    private $sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="image_url", type="text", nullable=true, options={"comment":"文章封面"})
     */
    private $image_url;

    /**
     * @var integer
     *
     * @ORM\Column(name="share_image_url", type="text", nullable=true, options={"comment":"分享图片"})
     */
    private $share_image_url;

    /**
     * @var integer
     *
     * @ORM\Column(name="release_time", type="integer", nullable=true, options={"comment":"文章发布时间"})
     */
    private $release_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="release_status", type="boolean", options={"comment":"文章发布状态", "default" : true})
     */
    private $release_status = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="author", type="string", nullable=true, options={"comment":"作者"})
     */
    private $author;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"作者id"})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="head_portrait", type="text", nullable=true, options={"comment":"作者头像"})
     */
    private $head_portrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"分销商id", "default": 0})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * general:普通文章;
     * bring  :带货文章;
     *
     * @ORM\Column(name="article_type", type="string", nullable=true, options={"comment":"文章类型，general:普通文章; bring:带货文章", "default": "general"})
     */
    private $article_type = 'general';

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="bigint", nullable=true, options={"comment":"文章类目id"})
     */
    private $category_id;

    /**
     * @var string
     *
     * @ORM\Column(name="province", type="string", nullable=true, options={"comment":"省"}))
     */
    private $province;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", nullable=true, options={"comment":"市"}))
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="area", type="string", nullable=true, options={"comment":"区"}))
     */
    private $area;

    /**
     * @var string
     *
     * @ORM\Column(name="regions_id", type="json_array", nullable=true, options={"comment":"地区编号集合"}))
     */
    private $regions_id;

    /**
     * @var string
     *
     * @ORM\Column(name="regions", type="json_array", nullable=true, options={"comment":"地区名称集合"}))
     */
    private $regions;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_ai", type="boolean", options={"comment":"是否AI生成，0表示人工创建，1表示AI生成", "default": false})
     */
    private $is_ai = false;

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
     * Get articleId
     *
     * @return integer
     */
    public function getArticleId()
    {
        return $this->article_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Article
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
     * Set title
     *
     * @param string $title
     *
     * @return Article
     */
    public function setTitle($title)
    {
        // FIXME: check performance
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        // FIXME: check performance
        return $this->title;
    }

    /**
     * Set summary
     *
     * @param string $summary
     *
     * @return Article
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Article
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return Article
     */
    public function setImageUrl($imageUrl)
    {
        $this->image_url = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }

    /**
     * Set releaseTime
     *
     * @param integer $releaseTime
     *
     * @return Article
     */
    public function setReleaseTime($releaseTime)
    {
        $this->release_time = $releaseTime;

        return $this;
    }

    /**
     * Get releaseTime
     *
     * @return integer
     */
    public function getReleaseTime()
    {
        return $this->release_time;
    }

    /**
     * Set releaseStatus
     *
     * @param boolean $releaseStatus
     *
     * @return Article
     */
    public function setReleaseStatus($releaseStatus)
    {
        $this->release_status = $releaseStatus;

        return $this;
    }

    /**
     * Get releaseStatus
     *
     * @return boolean
     */
    public function getReleaseStatus()
    {
        return $this->release_status;
    }

    /**
     * Set author
     *
     * @param string $author
     *
     * @return Article
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return Article
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return integer
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set headPortrait
     *
     * @param string $headPortrait
     *
     * @return Article
     */
    public function setHeadPortrait($headPortrait)
    {
        $this->head_portrait = $headPortrait;

        return $this;
    }

    /**
     * Get headPortrait
     *
     * @return string
     */
    public function getHeadPortrait()
    {
        return $this->head_portrait;
    }

    /**
     * Set articleType
     *
     * @param integer $articleType
     *
     * @return Article
     */
    public function setArticleType($articleType)
    {
        $this->article_type = $articleType;

        return $this;
    }

    /**
     * Get articleType
     *
     * @return integer
     */
    public function getArticleType()
    {
        return $this->article_type;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Article
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
     * @return Article
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
     * Set sort
     *
     * @param integer $sort
     *
     * @return Article
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     *
     * @return Article
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set province
     *
     * @param string $province
     *
     * @return Article
     */
    public function setProvince($province)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * Get province
     *
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Article
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set area
     *
     * @param string $area
     *
     * @return Article
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get area
     *
     * @return string
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Set regionsId
     *
     * @param array $regionsId
     *
     * @return Article
     */
    public function setRegionsId($regionsId)
    {
        $this->regions_id = $regionsId;

        return $this;
    }

    /**
     * Get regionsId
     *
     * @return array
     */
    public function getRegionsId()
    {
        return $this->regions_id;
    }

    /**
     * Set regions
     *
     * @param array $regions
     *
     * @return Article
     */
    public function setRegions($regions)
    {
        $this->regions = $regions;

        return $this;
    }

    /**
     * Get regions
     *
     * @return array
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return Article
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set shareImageUrl
     *
     * @param string $shareImageUrl
     *
     * @return Article
     */
    public function setShareImageUrl($shareImageUrl)
    {
        $this->share_image_url = $shareImageUrl;

        return $this;
    }

    /**
     * Get shareImageUrl
     *
     * @return string
     */
    public function getShareImageUrl()
    {
        return $this->share_image_url;
    }

    /**
     * Set isAi
     *
     * @param boolean $isAi
     *
     * @return Article
     */
    public function setIsAi($isAi)
    {
        $this->is_ai = $isAi;

        return $this;
    }

    /**
     * Get isAi
     *
     * @return boolean
     */
    public function getIsAi()
    {
        return $this->is_ai;
    }
}
