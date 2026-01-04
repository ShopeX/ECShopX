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
 * WxaNoticeTemplate(小程序通知消息模版)
 *
 * @ORM\Table(name="promotions_notice_template", options={"comment":"小程序通知消息模版"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\WxaNoticeTemplateRepository")
 */
class WxaNoticeTemplate
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
     * @var string
     *
     * @ORM\Column(name="template_name", type="string", options={"comment":"小程序模板名称 yykweishop 微商城等"})
     */
    private $template_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="wxa_template_id", type="string", options={"comment":"微信小程序通知模版库id"})
     */
    private $wxa_template_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"company_id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="notice_type", type="string", options={"comment":"通知类型","default":"wxa 小程序"})
     */
    private $notice_type;

    /**
     * @var string
     *
     * @ORM\Column(name="tmpl_type", type="string", options={"comment":"模板分类"})
     */
    private $tmpl_type;

    /**
     * @var string
     *
     * @ORM\Column(name="template_id", type="string", options={"comment":"模板id,发送小程序通知使用"})
     */
    private $template_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", options={"comment":"标题"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="scenes_name", type="string", options={"comment":"发送场景"})
     */
    private $scenes_name;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", options={"comment":"模板内容"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="is_open", type="boolean", options={"comment":"是否开启"})
     */
    private $is_open;

    /**
     * @var string
     *
     * @ORM\Column(name="send_time_desc", type="string", options={"comment":"短信发送触发时间描述,配置"})
     */
    private $send_time_desc;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WxaNoticeTemplate
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
     * Set noticeType
     *
     * @param string $noticeType
     *
     * @return WxaNoticeTemplate
     */
    public function setNoticeType($noticeType)
    {
        $this->notice_type = $noticeType;

        return $this;
    }

    /**
     * Get noticeType
     *
     * @return string
     */
    public function getNoticeType()
    {
        return $this->notice_type;
    }

    /**
     * Set tmplType
     *
     * @param string $tmplType
     *
     * @return WxaNoticeTemplate
     */
    public function setTmplType($tmplType)
    {
        $this->tmpl_type = $tmplType;

        return $this;
    }

    /**
     * Get tmplType
     *
     * @return string
     */
    public function getTmplType()
    {
        return $this->tmpl_type;
    }

    /**
     * Set templateId
     *
     * @param string $templateId
     *
     * @return WxaNoticeTemplate
     */
    public function setTemplateId($templateId)
    {
        $this->template_id = $templateId;

        return $this;
    }

    /**
     * Get templateId
     *
     * @return string
     */
    public function getTemplateId()
    {
        return $this->template_id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return WxaNoticeTemplate
     */
    public function setTitle($title)
    {
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
        return $this->title;
    }

    /**
     * Set scenesName
     *
     * @param string $scenesName
     *
     * @return WxaNoticeTemplate
     */
    public function setScenesName($scenesName)
    {
        $this->scenes_name = $scenesName;

        return $this;
    }

    /**
     * Get scenesName
     *
     * @return string
     */
    public function getScenesName()
    {
        return $this->scenes_name;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return WxaNoticeTemplate
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
     * Set isOpen
     *
     * @param boolean $isOpen
     *
     * @return WxaNoticeTemplate
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get isOpen
     *
     * @return boolean
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set sendTimeDesc
     *
     * @param string $sendTimeDesc
     *
     * @return WxaNoticeTemplate
     */
    public function setSendTimeDesc($sendTimeDesc)
    {
        $this->send_time_desc = $sendTimeDesc;

        return $this;
    }

    /**
     * Get sendTimeDesc
     *
     * @return string
     */
    public function getSendTimeDesc()
    {
        return $this->send_time_desc;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return WxaNoticeTemplate
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
     * Set wxaTemplateId
     *
     * @param string $wxaTemplateId
     *
     * @return WxaNoticeTemplate
     */
    public function setWxaTemplateId($wxaTemplateId)
    {
        $this->wxa_template_id = $wxaTemplateId;

        return $this;
    }

    /**
     * Get wxaTemplateId
     *
     * @return string
     */
    public function getWxaTemplateId()
    {
        return $this->wxa_template_id;
    }

    /**
     * Set templateName
     *
     * @param string $templateName
     *
     * @return WxaNoticeTemplate
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
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return WxaNoticeTemplate
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
