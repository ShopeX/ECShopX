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
 * CompanyRelKuaizhen 商城关联580快诊配置
 *
 * @ORM\Table(name="company_rel_kuaizhen", options={"comment":"商城关联580快诊配置", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="ThirdPartyBundle\Repositories\CompanyRelKuaizhenRepository")
 */
class CompanyRelKuaizhen
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
     * @ORM\Column(name="client_id", type="string", options={"comment":"client_id 580提供"})
     */
    private $client_id;

    /**
     * @var string
     *
     * @ORM\Column(name="client_secret", type="string", options={"comment":"client_secret 580提供"})
     */
    private $client_secret;

    /**
     * @var integer
     *
     * @ORM\Column(name="online", type="boolean", options={"default": 0, "comment":"是否上线:0:未开启，1:已开启"})
     */
    private $online;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_open", type="boolean", options={"default": 0, "comment":"是否开启:0:未开启，1:已开启"})
     */
    private $is_open;

    /**
     * @var integer
     *
     * @ORM\Column(name="kuaizhen_store_id", type="bigint", options={"comment":"kuaizhen580门店ID", "default": 0})
     */
    private $kuaizhen_store_id = 0;

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
        // ShopEx EcShopX Service Component
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return CompanyRelKuaizhen
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
     * Set clientId.
     *
     * @param string $clientId
     *
     * @return CompanyRelKuaizhen
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
     * Set clientSecret.
     *
     * @param string $clientSecret
     *
     * @return CompanyRelKuaizhen
     */
    public function setClientSecret($clientSecret)
    {
        $this->client_secret = $clientSecret;

        return $this;
    }

    /**
     * Get clientSecret.
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * Set online.
     *
     * @param bool $online
     *
     * @return CompanyRelKuaizhen
     */
    public function setOnline($online)
    {
        $this->online = $online;

        return $this;
    }

    /**
     * Get online.
     *
     * @return bool
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * Set isOpen.
     *
     * @param bool $isOpen
     *
     * @return CompanyRelKuaizhen
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get isOpen.
     *
     * @return bool
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set kuaizhenStoreId.
     *
     * @param int $kuaizhenStoreId
     *
     * @return CompanyRelKuaizhen
     */
    public function setKuaizhenStoreId(int $kuaizhenStoreId): CompanyRelKuaizhen
    {
        $this->kuaizhen_store_id = $kuaizhenStoreId;
        return $this;
    }

    /**
     * Get kuaizhenStoreId.
     *
     * @return int
     */
    public function getKuaizhenStoreId(): int
    {
        return $this->kuaizhen_store_id;
    }


    /**
     * Set created.
     *
     * @param int|null $created
     *
     * @return CompanyRelKuaizhen
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
     * @return CompanyRelKuaizhen
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
