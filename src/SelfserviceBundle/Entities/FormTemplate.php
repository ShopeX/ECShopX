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

namespace SelfserviceBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * FormTemplate 自助表单模板
 *
 * @ORM\Table(name="selfservice_form_template", options={"comment"="自助表单模板"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
 * @ORM\Entity(repositoryClass="SelfserviceBundle\Repositories\FormTemplateRepository")
 */
class FormTemplate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id", "default":0})
     */
    private $distributor_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="tem_name", type="string", options={"comment":"表单模板名称"})
     */
    private $tem_name;

    /**
     * @var string
     *
     * @ORM\Column(name="tem_type", type="string", options={"comment":"表单模板类型；ask_answer_paper：问答考卷，basic_entry：基础录入"})
     */
    private $tem_type;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", options={"comment":"表单模板内容"})
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"状态; 1:有效，2:弃用", "default": 1})
     */
    private $status = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="key_index", type="text", nullable=true, options={"comment":"表单关键指数"})
     */
    private $key_index;

    /**
     * @var integer
     *
     * @ORM\Column(name="form_style", type="string", nullable=true, options={"comment":"表单关键指数, single:单页问卷, multiple:多页问卷"})
     */
    private $form_style;

    /**
     * @var integer
     *
     * @ORM\Column(name="header_link_title", type="string", length=500, nullable=true, options={"comment":"头部文字"})
     */
    private $header_link_title;

    /**
     * @var string
     *
     * @ORM\Column(name="header_bg_pic", type="string", length=300, nullable=true, options={"comment":"头部背景图片"})
     */
    private $header_bg_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="header_height", type="string", length=20, nullable=true, options={"comment":"头部留白高度(px)"})
     */
    private $header_height;

    /**
     * @var string
     *
     * @ORM\Column(name="header_title", type="string", length=500, nullable=true, options={"comment":"头部文字内容"})
     */
    private $header_title;

    /**
     * @var string
     *
     * @ORM\Column(name="bottom_title", type="string", length=500, nullable=true, options={"comment":"表单关键指数"})
     */
    private $bottom_title;

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
     * Set id
     *
     * @param integer $id
     *
     * @return FormTemplate
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return FormTemplate
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

    public function setDistributorId($distributor_id)
    {
        $this->distributor_id = $distributor_id;
        return $this;
    }

    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * 设置头部背景图片
     *
     * @param string|null $header_bg_pic
     * @return $this
     */
    public function setHeaderBgPic($header_bg_pic)
    {
        $this->header_bg_pic = $header_bg_pic;
        return $this;
    }

    /**
     * 获取头部背景图片
     *
     * @return string|null
     */
    public function getHeaderBgPic()
    {
        return $this->header_bg_pic;
    }

    /**
     * 设置头部留白高度(px)
     *
     * @param string|null $header_height
     * @return $this
     */
    public function setHeaderHeight($header_height)
    {
        $this->header_height = $header_height;
        return $this;
    }

    /**
     * 获取头部留白高度(px)
     *
     * @return string|null
     */
    public function getHeaderHeight()
    {
        return $this->header_height;
    }

    /**
     * Set temName
     *
     * @param string $temName
     *
     * @return FormTemplate
     */
    public function setTemName($temName)
    {
        $this->tem_name = $temName;

        return $this;
    }

    /**
     * Get temName
     *
     * @return string
     */
    public function getTemName()
    {
        return $this->tem_name;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return FormTemplate
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
     * Set status
     *
     * @param integer $status
     *
     * @return FormTemplate
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set temType
     *
     * @param string $temType
     *
     * @return FormTemplate
     */
    public function setTemType($temType)
    {
        $this->tem_type = $temType;

        return $this;
    }

    /**
     * Get temType
     *
     * @return string
     */
    public function getTemType()
    {
        return $this->tem_type;
    }

    /**
     * Set keyIndex
     *
     * @param string $keyIndex
     *
     * @return FormTemplate
     */
    public function setKeyIndex($keyIndex)
    {
        $this->key_index = $keyIndex;

        return $this;
    }

    /**
     * Get keyIndex
     *
     * @return string
     */
    public function getKeyIndex()
    {
        return $this->key_index;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return FormTemplate
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
     * @return FormTemplate
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
     * Set formStyle
     *
     * @param string $formStyle
     *
     * @return FormTemplate
     */
    public function setFormStyle($formStyle)
    {
        $this->form_style = $formStyle;

        return $this;
    }

    /**
     * Get formStyle
     *
     * @return string
     */
    public function getFormStyle()
    {
        return $this->form_style;
    }

    /**
     * Set headerTitle
     *
     * @param string $headerTitle
     *
     * @return FormTemplate
     */
    public function setHeaderTitle($headerTitle)
    {
        $this->header_title = $headerTitle;

        return $this;
    }

    /**
     * Get headerTitle
     *
     * @return string
     */
    public function getHeaderTitle()
    {
        return $this->header_title;
    }

    /**
     * Set bottomTitle
     *
     * @param string $bottomTitle
     *
     * @return FormTemplate
     */
    public function setBottomTitle($bottomTitle)
    {
        $this->bottom_title = $bottomTitle;

        return $this;
    }

    /**
     * Get bottomTitle
     *
     * @return string
     */
    public function getBottomTitle()
    {
        return $this->bottom_title;
    }

    /**
     * Set headerLinkTitle.
     *
     * @param string|null $headerLinkTitle
     *
     * @return FormTemplate
     */
    public function setHeaderLinkTitle($headerLinkTitle = null)
    {
        $this->header_link_title = $headerLinkTitle;

        return $this;
    }

    /**
     * Get headerLinkTitle.
     *
     * @return string|null
     */
    public function getHeaderLinkTitle()
    {
        return $this->header_link_title;
    }
}
