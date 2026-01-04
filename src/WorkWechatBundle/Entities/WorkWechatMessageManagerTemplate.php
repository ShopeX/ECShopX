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
 * WorkWechatMessageManagerTemplate 企业微信管理员通知模板
 *
 * @ORM\Table(name="work_wechat_message_manager_template", options={"comment":"企业微信管理员通知模板"},
 *    indexes={
 *         @ORM\Index(name="idx_template_id", columns={"template_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="WorkWechatBundle\Repositories\WorkWechatMessageManagerTemplateRepository")
 */
class WorkWechatMessageManagerTemplate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"主键id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="template_id", type="string", options={"comment":"企业微信通知模板id"})
     */
    private $template_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"模版是否禁用", "default": false})
     */
    private $disabled;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set templateId.
     *
     * @param string $templateId
     *
     * @return WorkWechatMessageManagerTemplate
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
        return $this->template_id;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return WorkWechatMessageManagerTemplate
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
     * Set title.
     *
     * @param string|null $title
     *
     * @return WorkWechatMessageManagerTemplate
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
     * @return WorkWechatMessageManagerTemplate
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
}
