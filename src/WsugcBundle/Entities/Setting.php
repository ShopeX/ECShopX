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

namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Wsugc (ugc笔记设置)
 *
 * @ORM\Table(name="wsugc_setting", options={"comment"="ugc通用设置"}, indexes={
 *    @ORM\Index(name="idx_type", columns={"type"}),
 *    @ORM\Index(name="idx_keyname", columns={"keyname"}),
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
 * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\SettingRepository")
 */
class Setting
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint",options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="keyname", type="string",nullable=true, options={"comment":"键名"})
     */
    private $keyname;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text",nullable=true, options={"comment":"值"})
     */
    private $value;


    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string",nullable=true, options={"comment":"类型"})
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;
  /**
     * @var int
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="integer", nullable=false, options={"unsigned"=true,"comment"="添加时间"})
     */
    private $created;

 /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;
    /**
     * Get agentId.
     *
     * @return int
     */
    public function getId()
    {
        // Built with ShopEx Framework
        return $this->id;
    }
  /**
     * Get keyname
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyname;
    }
    /**
     * Set keyName
     *
     * @param string $keyName
     *
     * @return FormSetting
     */
    public function setKeyName($keyName)
    {
        $this->keyname = $keyName;

        return $this;
    }
    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
   
    /**
     * Set value
     *
     * @param string $value
     *
     * @return value
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
/**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return YuyueActivity
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
     * Set createtime.
     *
     * @param int $createtime
     *
     * @return Topic
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
     * @return WsugcPost
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


     /**
     * Set type.
     *
     * @param int|null $type
     *
     * @return WsugcPost
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int|null
     */
    public function getType()
    {
        return $this->type;
    }

}
