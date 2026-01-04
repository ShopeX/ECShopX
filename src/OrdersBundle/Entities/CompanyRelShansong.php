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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CompanyRelShansong 商城关联闪送应用配置信息表
 *
 * @ORM\Table(name="company_rel_shansong", options={"comment":"商城关联闪送应用配置信息表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\CompanyRelShansongRepository")
 */
class CompanyRelShansong
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
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
     * @ORM\Column(name="shop_id", type="string", options={"comment":"商户ID"})
     */
    private $shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="client_id", type="string", options={"comment":"App-key"})
     */
    private $client_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_secret", type="string", options={"comment":"App-密钥"})
     */
    private $app_secret;

    /**
     * @var integer
     *
     * @ORM\Column(name="online", type="boolean", options={"default": 0, "comment":"是否上线:0:未开启，1:已开启"})
     */
    private $online;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight_type", type="boolean", options={"default": 0, "comment":"运费承担方:0:商家承担，1:买家承担"})
     */
    private $freight_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_open", type="boolean", options={"default": 0, "comment":"是否开启:0:未开启，1:已开启"})
     */
    private $is_open;

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="integer", nullable=true,  options={"comment":"创建时间"})
     */
    private $created;

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated", type="integer", nullable=true,  options={"comment":"更新时间"})
     */
    private $updated;

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
     * @return CompanyRelShansong
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
     * Set shopId.
     *
     * @param string $shopId
     *
     * @return CompanyRelShansong
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId.
     *
     * @return string
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set clientId.
     *
     * @param string $clientId
     *
     * @return CompanyRelShansong
     */
    public function setClientId($clientId)
    {
        $this->client_id = $clientId;

        return $this;
    }

    /**
     * Get clientId.
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * Set appSecret.
     *
     * @param string $appSecret
     *
     * @return CompanyRelShansong
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
     * Set online.
     *
     * @param int $online
     *
     * @return CompanyRelShansong
     */
    public function setOnline($online)
    {
        $this->online = $online;

        return $this;
    }

    /**
     * Get online.
     *
     * @return int
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * Set freightType.
     *
     * @param int $freightType
     *
     * @return CompanyRelShansong
     */
    public function setFreightType($freightType)
    {
        $this->freight_type = $freightType;

        return $this;
    }

    /**
     * Get freightType.
     *
     * @return int
     */
    public function getFreightType()
    {
        return $this->freight_type;
    }

    /**
     * Set is_open.
     *
     * @param int $is_open
     *
     * @return CompanyRelShansong
     */
    public function setIsOpen($is_open)
    {
        $this->is_open = $is_open;

        return $this;
    }

    /**
     * Get is_open.
     *
     * @return int
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set created.
     *
     * @param int|null $created
     *
     * @return CompanyRelShansong
     */
    public function setCreated($created = null)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int|null
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
     * @return CompanyRelShansong
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
