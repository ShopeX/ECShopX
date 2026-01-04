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

namespace WorkWechatBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * WorkWechatMessageTemplate 企业微信通知模板
 *
 * @ORM\Table(name="work_wechat_message_template", options={"comment":"企业微信通知模板"})
 * @ORM\Entity(repositoryClass="WorkWechatBundle\Repositories\WorkWechatMessageTemplateRepository")
 */
class WorkWechatMessageTemplate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"企业微信通知模板"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="template_id", type="string", options={"comment":"企业微信通知模板id"})
     */
    private $template_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"模版是否开启", "default": false})
     */
    private $disabled;

    /**
     * @var boolean
     *
     * @ORM\Column(name="emphasis_first_item", type="boolean", options={"comment":"是否放大第一个", "default": false})
     */
    private $emphasis_first_item;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", nullable=true, options={"comment":"企业微信通知模板标题", "default": ""})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", nullable=true, options={"comment":"企业微信通知模板内容", "default": ""})
     */
    private $description;

    /**
     * @var json_array
     *
     * @ORM\Column(name="content", type="json_array", nullable=true, options={"comment":"通知主体消息"})
     */
    private $content;

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
     * @return WorkWechatMessageTemplate
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
        // IDX: 2367340174
        return $this->company_id;
    }

    /**
     * Set templateId.
     *
     * @param string $templateId
     *
     * @return WorkWechatMessageTemplate
     */
    public function setTemplateId($templateId)
    {
        $this->template_id = $templateId;

        return $this;
    }

    /**
     * Get templateId.
     *
     * @return string
     */
    public function getTemplateId()
    {
        // IDX: 2367340174
        return $this->template_id;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return WorkWechatMessageTemplate
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set emphasisFirstItem.
     *
     * @param bool $emphasisFirstItem
     *
     * @return WorkWechatMessageTemplate
     */
    public function setEmphasisFirstItem($emphasisFirstItem)
    {
        $this->emphasis_first_item = $emphasisFirstItem;

        return $this;
    }

    /**
     * Get emphasisFirstItem.
     *
     * @return bool
     */
    public function getEmphasisFirstItem()
    {
        return $this->emphasis_first_item;
    }

    /**
     * Set title.
     *
     * @param string|null $title
     *
     * @return WorkWechatMessageTemplate
     */
    public function setTitle($title = null)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return WorkWechatMessageTemplate
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
     * Set content.
     *
     * @param array|null $content
     *
     * @return WorkWechatMessageTemplate
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return array|null
     */
    public function getContent()
    {
        return $this->content;
    }
}
