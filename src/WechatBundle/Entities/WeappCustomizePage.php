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
 * WeappCustomizePage 小程序自定义页面
 *
 * @ORM\Table(name="wechat_weapp_customize_page", options={"comment":"小程序自定义页面"})
 * @ORM\Entity(repositoryClass="WechatBundle\Repositories\WeappCustomizePageRepository")
 */
class WeappCustomizePage
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
     * @ORM\Column(name="template_name", type="string", nullable=true, options={"comment":"小程序模板名称"})
     */
    private $template_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="page_name", type="string", options={"comment":"页面名称"})
     */
    private $page_name;

    /**
     * @var string
     *
     * @ORM\Column(name="page_description", type="string", options={"comment":"页面描述"})
     */
    private $page_description;

    /**
     * @var string
     *
     * @ORM\Column(name="page_share_title", type="string", nullable=true, options={"comment":"分享标题"})
     */
    private $page_share_title;

    /**
     * @var string
     *
     * @ORM\Column(name="page_share_desc", type="string", nullable=true, options={"comment":"分享描述"})
     */
    private $page_share_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="page_share_imageUrl", type="string", nullable=true, options={"comment":"分享图片"})
     */
    private $page_share_imageUrl;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_open", type="integer", options={"default":0, "comment":"是否开启0否1是"})
     */
    private $is_open = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="page_type", type="string", options={"default":"normal", "comment":"页面类型 salesperson:导购首页"})
     */
    private $page_type = 'normal';
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
     * @return WeappCustomizePage
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
     * @return WeappCustomizePage
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
     * Set pageName
     *
     * @param string $pageName
     *
     * @return WeappCustomizePage
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
     * Set pageDescription
     *
     * @param string $pageDescription
     *
     * @return WeappCustomizePage
     */
    public function setPageDescription($pageDescription)
    {
        $this->page_description = $pageDescription;

        return $this;
    }

    /**
     * Get pageDescription
     *
     * @return string
     */
    public function getPageDescription()
    {
        return $this->page_description;
    }

    /**
     * Set isOpen
     *
     * @param integer $isOpen
     *
     * @return WeappCustomizePage
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get isOpen
     *
     * @return integer
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set pageShareTitle.
     *
     * @param string|null $pageShareTitle
     *
     * @return WeappCustomizePage
     */
    public function setPageShareTitle($pageShareTitle = null)
    {
        $this->page_share_title = $pageShareTitle;

        return $this;
    }

    /**
     * Get pageShareTitle.
     *
     * @return string|null
     */
    public function getPageShareTitle()
    {
        return $this->page_share_title;
    }

    /**
     * Set pageShareDesc.
     *
     * @param string|null $pageShareDesc
     *
     * @return WeappCustomizePage
     */
    public function setPageShareDesc($pageShareDesc = null)
    {
        $this->page_share_desc = $pageShareDesc;

        return $this;
    }

    /**
     * Get pageShareDesc.
     *
     * @return string|null
     */
    public function getPageShareDesc()
    {
        return $this->page_share_desc;
    }

    /**
     * Set pageShareImageUrl.
     *
     * @param string|null $pageShareImageUrl
     *
     * @return WeappCustomizePage
     */
    public function setPageShareImageUrl($pageShareImageUrl = null)
    {
        $this->page_share_imageUrl = $pageShareImageUrl;

        return $this;
    }

    /**
     * Get pageShareImageUrl.
     *
     * @return string|null
     */
    public function getPageShareImageUrl()
    {
        return $this->page_share_imageUrl;
    }



    /**
     * Set pageType.
     *
     * @param string $pageType
     *
     * @return WeappCustomizePage
     */
    public function setPageType($pageType)
    {
        $this->page_type = $pageType;

        return $this;
    }

    /**
     * Get pageType.
     *
     * @return string
     */
    public function getPageType()
    {
        return $this->page_type;
    }
}
