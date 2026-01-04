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

namespace WechatBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * WeappSetting 小程序模板装修表
 *
 * @ORM\Table(name="wechat_weapp_setting", options={"comment":"小程序模板装修表"}, indexes={
 *    @ORM\Index(name="ix_page_nme", columns={"page_name"}),
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_pages_template_id", columns={"pages_template_id"}),
 *    @ORM\Index(name="idx_pagename_version_name_companyid_templatename", columns={"page_name", "version", "name", "company_id", "template_name"}),
 * })
 * @ORM\Entity(repositoryClass="WechatBundle\Repositories\WeappSettingRepository")
 */
class WeappSetting
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="template_name", type="string", length=50, options={"comment":"小程序模板名称"})
     */
    private $template_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="page_name", type="string", length=50, options={"comment":"页面名称"})
     */
    private $page_name;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, options={"comment":"配置名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=10, options={"comment":"配置版本", "default":"v1.0.0"})
     */
    private $version = 'v1.0.0';

    /**
     * @var string
     *
     * @ORM\Column(name="params", type="text", options={"comment":"配置参数"})
     */
    private $params;


    /**
     * @var integer
     *
     * @ORM\Column(name="pages_template_id", nullable=true, type="integer", options={"comment":"页面模板id", "default":0})
     */
    private $pages_template_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort_by", nullable=true, type="integer", options={"comment":"排序", "default":0})
     */
    private $sort_by;

    public function setSortBy($sortBy)
    {
        $this->sort_by = $sortBy;
        return $this;
    }

    public function getSortBy()
    {
        return $this->sort_by;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set templateName
     *
     * @param string $templateName
     *
     * @return WeappSetting
     */
    public function setTemplateName($templateName)
    {
        $this->template_name = $templateName;

        return $this;
    }

    /**
     * Get templateName
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WeappSetting
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
     * Set name
     *
     * @param string $name
     *
     * @return WeappSetting
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set params
     *
     * @param string $params
     *
     * @return WeappSetting
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set pageName
     *
     * @param string $pageName
     *
     * @return WeappSetting
     */
    public function setPageName($pageName)
    {
        $this->page_name = $pageName;

        return $this;
    }

    /**
     * Get pageName
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->page_name;
    }

    /**
     * Set version
     *
     * @param string $version
     *
     * @return WeappSetting
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set pagesTemplateId.
     *
     * @param int|null $pagesTemplateId
     *
     * @return WeappSetting
     */
    public function setPagesTemplateId($pagesTemplateId = null)
    {
        $this->pages_template_id = $pagesTemplateId;

        return $this;
    }

    /**
     * Get pagesTemplateId.
     *
     * @return int|null
     */
    public function getPagesTemplateId()
    {
        return $this->pages_template_id;
    }
}
