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

namespace ThirdPartyBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MapConfig 地图配置表
 *
 * @ORM\Table(name="map_config", options={"comment":"地图配置表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="ThirdPartyBundle\Repositories\MapConfigRepository")
 */
class MapConfig
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"地区id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, nullable=false, options={"comment":"第三方类型【amap 高德地图】【tencent 腾讯地图】"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="app_key", type="string", nullable=false, options={"comment":"第三方控制台中生成的key", "default": ""})
     */
    private $app_key;

    /**
     * @var string
     *
     * @ORM\Column(name="app_secret", type="string", nullable=false, options={"comment":"第三方控制台中生成的秘钥", "default": ""})
     */
    private $app_secret;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=false, options={"comment":"是否是默认的地图配置项", "default": "0"})
     */
    private $is_default;

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
     * @return MapConfig
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
     * Set type.
     *
     * @param string $type
     *
     * @return MapConfig
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set appKey.
     *
     * @param string $appKey
     *
     * @return MapConfig
     */
    public function setAppKey($appKey)
    {
        $this->app_key = $appKey;

        return $this;
    }

    /**
     * Get appKey.
     *
     * @return string
     */
    public function getAppKey()
    {
        return $this->app_key;
    }

    /**
     * Set appSecret.
     *
     * @param string $appSecret
     *
     * @return MapConfig
     */
    public function setAppSecret($appSecret)
    {
        $this->app_secret = $appSecret;

        return $this;
    }

    /**
     * Get appSecret.
     *
     * @return string
     */
    public function getAppSecret()
    {
        return $this->app_secret;
    }

    /**
     * Set isDefault.
     *
     * @param bool $isDefault
     *
     * @return MapConfig
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault.
     *
     * @return bool
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return MapConfig
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
     * @return MapConfig
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
