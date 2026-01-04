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
 * Idiograph(短信模板表)
 *
 * @ORM\Table(name="sms_template", options={"comment":"短信模板表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\SmsTemplateRepository")
 */
class SmsTemplate
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
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"company_id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sms_type", type="string", options={"comment":"短信类型","default":"notice"})
     */
    private $sms_type;

    /**
     * @var string
     *
     * @ORM\Column(name="tmpl_type", type="string", options={"comment":"模板分类"})
     */
    private $tmpl_type;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", options={"comment":"模板内容"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="is_open", type="string", options={"comment":"是否开启"})
     */
    private $is_open;

    /**
     * @var string
     *
     * @ORM\Column(name="tmpl_name", type="string", options={"comment":"模板名称"})
     */
    private $tmpl_name;

    /**
     * @var string
     *
     * @ORM\Column(name="send_time_desc", type="string", options={"comment":"短信发送触发时间描述"})
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
     * @return SmsTemplate
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
     * Set smsType
     *
     * @param string $smsType
     *
     * @return SmsTemplate
     */
    public function setSmsType($smsType)
    {
        $this->sms_type = $smsType;

        return $this;
    }

    /**
     * Get smsType
     *
     * @return string
     */
    public function getSmsType()
    {
        return $this->sms_type;
    }

    /**
     * Set tmplType
     *
     * @param string $tmplType
     *
     * @return SmsTemplate
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
     * Set content
     *
     * @param string $content
     *
     * @return SmsTemplate
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
     * @param string $isOpen
     *
     * @return SmsTemplate
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get isOpen
     *
     * @return string
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set tmplName
     *
     * @param string $tmplName
     *
     * @return SmsTemplate
     */
    public function setTmplName($tmplName)
    {
        $this->tmpl_name = $tmplName;

        return $this;
    }

    /**
     * Get tmplName
     *
     * @return string
     */
    public function getTmplName()
    {
        return $this->tmpl_name;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return SmsTemplate
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
     * Set sendTimeDesc
     *
     * @param string $sendTimeDesc
     *
     * @return SmsTemplate
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
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return SmsTemplate
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
