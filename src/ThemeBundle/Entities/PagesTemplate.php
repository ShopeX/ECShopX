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
use LaravelDoctrine\Extensions\Timestamps\Timestamps;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * pages_template 页面模板表
 *
 * @ORM\Table(name="pages_template", options={"comment":"页面模板表"},
 * indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\PagesTemplateRepository")
 */
class PagesTemplate
{
    use Timestamps;
    use SoftDeletes;

    /**
     * @var integer
     *
     * @ORM\Column(name="pages_template_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $pages_template_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_id", nullable=true, type="integer", options={"comment":"店铺id", "default":0})
     */
    private $distributor_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="template_title", type="string", length=50, options={"comment":"模板名称"})
     */
    private $template_title;

    /**
     * @var string
     *
     * @ORM\Column(name="template_name", type="string", length=50,  options={"comment":"模板客户端名称"})
     */
    private $template_name;

    /**
     * @var string
     *
     * @ORM\Column(name="template_pic", nullable=true, type="string", options={"comment":"模板封面"})
     */
    private $template_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="template_type", type="integer", options={"comment":"模板类型 0总部  1同步模板 2门店自有模板", "default":0})
     */
    private $template_type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="element_edit_status", nullable=true, type="integer", options={"comment":"可编辑挂件状态  1启用 2未启用", "default":2})
     */
    private $element_edit_status = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="status", nullable=true, type="integer", options={"comment":"启用状态 1启用 2未启用", "default":2})
     */
    private $status = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="timer_status", nullable=true, type="integer", options={"comment":"定时启用状态 1启用 2未启用", "default":2})
     */
    private $timer_status = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="timer_time", nullable=true, type="integer", options={"comment":"定时模板切换时间"})
     */
    private $timer_time;

    /**
     * @var string
     *
     * @ORM\Column(name="template_status_modify_time", nullable=true, type="integer", options={"comment":"模板状态变更时间"})
     */
    private $template_status_modify_time;

    /**
     * @var string
     *
     * @ORM\Column(name="weapp_pages", nullable=true, type="string", options={"comment":"模版页面"})
     */
    private $weapp_pages;

    /**
     * @var string
     *
     * @ORM\Column(name="template_content", nullable=true, type="text", options={"comment":"模板内容"})
     */
    private $template_content;


    /**
     * @var string
     *
     * @ORM\Column(name="lang", nullable=true, type="string", options={"comment":"模版语言"})
     */
    private $lang;

    /**
     * Get pagesTemplateId.
     *
     * @return int
     */
    public function getPagesTemplateId()
    {
        return $this->pages_template_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PagesTemplate
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
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return PagesTemplate
     */
    public function setDistributorId($distributorId = null)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set templateName.
     *
     * @param string $templateName
     *
     * @return PagesTemplate
     */
    public function setTemplateName($templateName)
    {
        $this->template_name = $templateName;

        return $this;
    }

    /**
     * Get templateName.
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * Set templatePic.
     *
     * @param string|null $templatePic
     *
     * @return PagesTemplate
     */
    public function setTemplatePic($templatePic = null)
    {
        $this->template_pic = $templatePic;

        return $this;
    }

    /**
     * Get templatePic.
     *
     * @return string|null
     */
    public function getTemplatePic()
    {
        return $this->template_pic;
    }

    /**
     * Set templateType.
     *
     * @param int $templateType
     *
     * @return PagesTemplate
     */
    public function setTemplateType($templateType)
    {
        $this->template_type = $templateType;

        return $this;
    }

    /**
     * Get templateType.
     *
     * @return int
     */
    public function getTemplateType()
    {
        return $this->template_type;
    }

    /**
     * Set elementEditStatus.
     *
     * @param int|null $elementEditStatus
     *
     * @return PagesTemplate
     */
    public function setElementEditStatus($elementEditStatus = null)
    {
        $this->element_edit_status = $elementEditStatus;

        return $this;
    }

    /**
     * Get elementEditStatus.
     *
     * @return int|null
     */
    public function getElementEditStatus()
    {
        return $this->element_edit_status;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return PagesTemplate
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
     * Set timerStatus.
     *
     * @param int|null $timerStatus
     *
     * @return PagesTemplate
     */
    public function setTimerStatus($timerStatus = null)
    {
        $this->timer_status = $timerStatus;

        return $this;
    }

    /**
     * Get timerStatus.
     *
     * @return int|null
     */
    public function getTimerStatus()
    {
        return $this->timer_status;
    }

    /**
     * Set timerTime.
     *
     * @param int|null $timerTime
     *
     * @return PagesTemplate
     */
    public function setTimerTime($timerTime = null)
    {
        $this->timer_time = $timerTime;

        return $this;
    }

    /**
     * Get timerTime.
     *
     * @return int|null
     */
    public function getTimerTime()
    {
        return $this->timer_time;
    }

    /**
     * Set templateStatusModifyTime.
     *
     * @param int|null $templateStatusModifyTime
     *
     * @return PagesTemplate
     */
    public function setTemplateStatusModifyTime($templateStatusModifyTime = null)
    {
        $this->template_status_modify_time = $templateStatusModifyTime;

        return $this;
    }

    /**
     * Get templateStatusModifyTime.
     *
     * @return int|null
     */
    public function getTemplateStatusModifyTime()
    {
        return $this->template_status_modify_time;
    }

    /**
     * Set weappPages.
     *
     * @param string|null $weappPages
     *
     * @return PagesTemplate
     */
    public function setWeappPages($weappPages = null)
    {
        $this->weapp_pages = $weappPages;

        return $this;
    }

    /**
     * Get weappPages.
     *
     * @return string|null
     */
    public function getWeappPages()
    {
        return $this->weapp_pages;
    }

    /**
     * Set templateContent.
     *
     * @param string|null $templateContent
     *
     * @return PagesTemplate
     */
    public function setTemplateContent($templateContent = null)
    {
        $this->template_content = $templateContent;

        return $this;
    }

    /**
     * Get templateContent.
     *
     * @return string|null
     */
    public function getTemplateContent()
    {
        return $this->template_content;
    }

    /**
     * Set templateTitle.
     *
     * @param string $templateTitle
     *
     * @return PagesTemplate
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

    public function getLang()
    {
        return $this->lang;
    }

    public function setLang(string $lang)
    {
        $this->lang = $lang;
    }
}
