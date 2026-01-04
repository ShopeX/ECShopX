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

namespace ThemeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * theme_pc_template pc页面装修
 *
 * @ORM\Table(name="theme_pc_template", options={"comment":"pc页面装修"},
 * indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\ThemePcTemplateRepository")
 */
class ThemePcTemplate
{
    use SoftDeletes;

    /**
     * @var integer
     *
     * @ORM\Column(name="theme_pc_template_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $theme_pc_template_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="template_title", type="string", length=50, options={"comment":"页面名称"})
     */
    private $template_title;

    /**
     * @var string
     *
     * @ORM\Column(name="template_description", type="string", length=150, options={"comment":"页面描述"})
     */
    private $template_description;

    /**
     * @var string
     *
     * @ORM\Column(name="page_type", nullable=true, type="string", length=15, options={"comment":"页面类型 index 首页 custom ", "default": "index"})
     */
    private $page_type = 'index';

    /**
     * @var integer
     *
     * @ORM\Column(name="status", nullable=true, type="integer", options={"comment":"启用状态 1启用 2未启用", "default":2})
     */
    private $status = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="version", nullable=true, type="string", length=10, options={"comment":"版本号"})
     */
    private $version;

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
     * Get themePcTemplateId.
     *
     * @return int
     */
    public function getThemePcTemplateId()
    {
        return $this->theme_pc_template_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ThemePcTemplate
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
     * Set templateTitle.
     *
     * @param string $templateTitle
     *
     * @return ThemePcTemplate
     */
    public function setTemplateTitle($templateTitle)
    {
        $this->template_title = $templateTitle;

        return $this;
    }

    /**
     * Get templateTitle.
     *
     * @return string
     */
    public function getTemplateTitle()
    {
        return $this->template_title;
    }

    /**
     * Set templateDescription.
     *
     * @param string $templateDescription
     *
     * @return ThemePcTemplate
     */
    public function setTemplateDescription($templateDescription)
    {
        $this->template_description = $templateDescription;

        return $this;
    }

    /**
     * Get templateDescription.
     *
     * @return string
     */
    public function getTemplateDescription()
    {
        return $this->template_description;
    }

    /**
     * Set pageType.
     *
     * @param string|null $pageType
     *
     * @return ThemePcTemplate
     */
    public function setPageType($pageType = null)
    {
        $this->page_type = $pageType;

        return $this;
    }

    /**
     * Get pageType.
     *
     * @return string|null
     */
    public function getPageType()
    {
        return $this->page_type;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return ThemePcTemplate
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set version.
     *
     * @param string|null $version
     *
     * @return ThemePcTemplate
     */
    public function setVersion($version = null)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ThemePcTemplate
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
     * @return ThemePcTemplate
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
