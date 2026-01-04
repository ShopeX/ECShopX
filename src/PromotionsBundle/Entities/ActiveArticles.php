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

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ActiveArticles 活动文章表
 *
 * @ORM\Table(name="promotions_active_articles", options={"comment":"活动文章表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\ActiveArticlesRepository")
 */
class ActiveArticles
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="article_title", type="string", options={"comment":"文章标题"})
     */
    private $article_title;

    /**
     * @var string
     *
     * @ORM\Column(name="article_subtitle", nullable=true, type="string", options={"comment":"文章副标题"})
     */
    private $article_subtitle;

    /**
     * @var string
     *
     * @ORM\Column(name="article_content", type="text", options={"comment":"文章内容"})
     */
    private $article_content;

    /**
     * @var string
     *
     * @ORM\Column(name="article_cover", type="text", options={"comment":"封面"})
     */
    private $article_cover;

    /**
     * @var string
     *
     * @ORM\Column(name="directional_url", type="text", options={"comment":"跳转地址,转json"})
     */
    private $directional_url;

    /**
     * @var int
     *
     * @ORM\Column(name="is_show", type="boolean", options={"comment":"是否展示,1展示 0不展示", "default":1})
     */
    private $is_show;

    /**
     * @var int
     *
     * @ORM\Column(name="is_delete", type="boolean", options={"comment":"是否已删除,1已删除 0未删除", "default":0})
     */
    private $is_delete = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="bigint", nullable=true, options={"comment":"排序", "default":0})
     */
    private $sort = 0;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated;

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
     * @return ActiveArticles
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
     * Set articleTitle.
     *
     * @param string $articleTitle
     *
     * @return ActiveArticles
     */
    public function setArticleTitle($articleTitle)
    {
        $this->article_title = $articleTitle;

        return $this;
    }

    /**
     * Get articleTitle.
     *
     * @return string
     */
    public function getArticleTitle()
    {
        return $this->article_title;
    }

    /**
     * Set articleSubtitle.
     *
     * @param string|null $articleSubtitle
     *
     * @return ActiveArticles
     */
    public function setArticleSubtitle($articleSubtitle = null)
    {
        $this->article_subtitle = $articleSubtitle;

        return $this;
    }

    /**
     * Get articleSubtitle.
     *
     * @return string|null
     */
    public function getArticleSubtitle()
    {
        return $this->article_subtitle;
    }

    /**
     * Set articleContent.
     *
     * @param string $articleContent
     *
     * @return ActiveArticles
     */
    public function setArticleContent($articleContent)
    {
        $this->article_content = $articleContent;

        return $this;
    }

    /**
     * Get articleContent.
     *
     * @return string
     */
    public function getArticleContent()
    {
        return $this->article_content;
    }

    /**
     * Set articleCover.
     *
     * @param string $articleCover
     *
     * @return ActiveArticles
     */
    public function setArticleCover($articleCover)
    {
        $this->article_cover = $articleCover;

        return $this;
    }

    /**
     * Get articleCover.
     *
     * @return string
     */
    public function getArticleCover()
    {
        return $this->article_cover;
    }

    /**
     * Set directionalUrl.
     *
     * @param string $directionalUrl
     *
     * @return ActiveArticles
     */
    public function setDirectionalUrl($directionalUrl)
    {
        $this->directional_url = $directionalUrl;

        return $this;
    }

    /**
     * Get directionalUrl.
     *
     * @return string
     */
    public function getDirectionalUrl()
    {
        return $this->directional_url;
    }

    /**
     * Set isShow.
     *
     * @param bool $isShow
     *
     * @return ActiveArticles
     */
    public function setIsShow($isShow)
    {
        $this->is_show = $isShow;

        return $this;
    }

    /**
     * Get isShow.
     *
     * @return bool
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * Set isDelete.
     *
     * @param bool $isDelete
     *
     * @return ActiveArticles
     */
    public function setIsDelete($isDelete)
    {
        $this->is_delete = $isDelete;

        return $this;
    }

    /**
     * Get isDelete.
     *
     * @return bool
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * Set sort.
     *
     * @param bool|null $sort
     *
     * @return ActiveArticles
     */
    public function setSort($sort = null)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return bool|null
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ActiveArticles
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
     * @return ActiveArticles
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
