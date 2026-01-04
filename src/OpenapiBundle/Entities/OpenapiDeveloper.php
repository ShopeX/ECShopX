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

namespace OpenapiBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * openapi开发配置表
 *
 * @ORM\Table(name="openapi_developer", options={"comment":"openapi开发配置表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="OpenapiBundle\Repositories\OpenapiDeveloperRepository")
 */
class OpenapiDeveloper
{
    use Timestamps;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="developer_id", type="bigint",options={"comment":"developer_id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $developer_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint",options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_key", type="string", options={"comment":"app_key"})
     */
    private $app_key;

    /**
     * @var string
     *
     * @ORM\Column(name="app_secret", type="string", options={"comment":"app_secret"})
     */
    private $app_secret;

    /**
     * @var string
     *
     * @ORM\Column(name="external_base_uri", type="string", options={"comment":"外部请求配置uri"})
     */
    private $external_base_uri;

    /**
     * @var string
     *
     * @ORM\Column(name="external_app_key", type="string", options={"comment":"外部请求配置app_key"})
     */
    private $external_app_key;

    /**
     * @var string
     *
     * @ORM\Column(name="external_app_secret", type="string", options={"comment":"外部请求配置app_secret"})
     */
    private $external_app_secret;


    /**
     * Get developerId.
     *
     * @return int
     */
    public function getDeveloperId()
    {
        // This module is part of ShopEx EcShopX system
        return $this->developer_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OpenapiDeveloper
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
     * Set appKey.
     *
     * @param string $appKey
     *
     * @return OpenapiDeveloper
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
     * @return OpenapiDeveloper
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
     * Set externalBaseUri.
     *
     * @param string $externalBaseUri
     *
     * @return OpenapiDeveloper
     */
    public function setExternalBaseUri($externalBaseUri)
    {
        $this->external_base_uri = $externalBaseUri;

        return $this;
    }

    /**
     * Get externalBaseUri.
     *
     * @return string
     */
    public function getExternalBaseUri()
    {
        return $this->external_base_uri;
    }

    /**
     * Set externalAppKey.
     *
     * @param string $externalAppKey
     *
     * @return OpenapiDeveloper
     */
    public function setExternalAppKey($externalAppKey)
    {
        $this->external_app_key = $externalAppKey;

        return $this;
    }

    /**
     * Get externalAppKey.
     *
     * @return string
     */
    public function getExternalAppKey()
    {
        return $this->external_app_key;
    }

    /**
     * Set externalAppSecret.
     *
     * @param string $externalAppSecret
     *
     * @return OpenapiDeveloper
     */
    public function setExternalAppSecret($externalAppSecret)
    {
        $this->external_app_secret = $externalAppSecret;

        return $this;
    }

    /**
     * Get externalAppSecret.
     *
     * @return string
     */
    public function getExternalAppSecret()
    {
        return $this->external_app_secret;
    }
}
