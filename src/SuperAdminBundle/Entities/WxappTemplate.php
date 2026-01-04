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

namespace SuperAdminBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WeappTemplate  小程序系统模板
 *
 * @ORM\Table(name="superadmin_wxapp_template",options={"comment"="小程序模板表"})
 * @ORM\Entity(repositoryClass="SuperAdminBundle\Repositories\WxappTemplateRepository")
 */
class WxappTemplate
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
     * @ORM\Column(name="key_name", type="string", options={"comment":"小程序英文描述"}, unique=true)
     */
    private $key_name;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true, options={"comment":"小程序模板名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="tag", type="string", nullable=true, options={"comment":"小程序标签"})
     */
    private $tag;

    /**
     * @var string
     *
     * @ORM\Column(name="template_id", type="integer", nullable=true, options={"comment":"模板id"})
     */
    private $template_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="template_id_2", type="integer", nullable=true, options={"comment":"模板id(直播版)"})
     */
    private $template_id_2 = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", nullable=true, options={"comment":"版本号"})
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="is_only", type="boolean", options={"comment":"是否为唯一属性，如果为唯一属性那么当前模版只能绑定一个小程序", "default": false})
     */
    private $is_only = false;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", nullable=true, options={"comment":"模板详细描述"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="text", nullable=true, options={"comment":"合法域名配置"})
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(name="is_disabled", type="boolean", options={"comment":"是否禁用","default": false})
     */
    private $is_disabled = false;

    /**
     * @var string
     *
     * @ORM\Column(name="platform", type="string", options={"comment":"使用平台。可选值有 development-开发环境;preissue-预发布环境;production-正式环境;","default": "development"})
     */
    private $platform = "development";

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
     * Set keyName
     *
     * @param string $keyName
     *
     * @return WxappTemplate
     */
    public function setKeyName($keyName)
    {
        $this->key_name = $keyName;

        return $this;
    }

    /**
     * Get keyName
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->key_name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return WxappTemplate
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
     * Set tag
     *
     * @param string $tag
     *
     * @return WxappTemplate
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set templateId
     *
     * @param integer $templateId
     *
     * @return WxappTemplate
     */
    public function setTemplateId($templateId)
    {
        $this->template_id = $templateId;

        return $this;
    }

    /**
     * Get templateId
     *
     * @return integer
     */
    public function getTemplateId()
    {
        return $this->template_id;
    }

    /**
     * Set version
     *
     * @param string $version
     *
     * @return WxappTemplate
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
     * Set isOnly
     *
     * @param boolean $isOnly
     *
     * @return WxappTemplate
     */
    public function setIsOnly($isOnly)
    {
        $this->is_only = $isOnly;

        return $this;
    }

    /**
     * Get isOnly
     *
     * @return boolean
     */
    public function getIsOnly()
    {
        return $this->is_only;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return WxappTemplate
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set domain
     *
     * @param string $domain
     *
     * @return WxappTemplate
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set isDisabled
     *
     * @param boolean $isDisabled
     *
     * @return WxappTemplate
     */
    public function setIsDisabled($isDisabled)
    {
        $this->is_disabled = $isDisabled;

        return $this;
    }

    /**
     * Get isDisabled
     *
     * @return boolean
     */
    public function getIsDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * Set platform
     *
     * @param string $platform
     *
     * @return WxappTemplate
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * Get platform
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return WxappTemplate
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
     * @return WxappTemplate
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
     * Set templateId2.
     *
     * @param int|null $templateId2
     *
     * @return WxappTemplate
     */
    public function setTemplateId2($templateId2 = null)
    {
        $this->template_id_2 = $templateId2;

        return $this;
    }

    /**
     * Get templateId2.
     *
     * @return int|null
     */
    public function getTemplateId2()
    {
        return $this->template_id_2;
    }
}
