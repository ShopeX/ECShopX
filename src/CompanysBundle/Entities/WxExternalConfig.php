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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * WxExternalConfig 外部小程序配置表
 *
 * @ORM\Table(name="wx_external_config", options={"comment":"外部小程序配置表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\WxExternalConfigRepository")
 */

class WxExternalConfig
{
    use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="wx_external_config_id", type="bigint", options={"comment":"外部小程序配置表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $wx_external_config_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", options={"comment":"小程序APPID"})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_name", type="string", nullable=true, options={"comment":"小程序名称"})
     */
    private $app_name;

    /**
     * @var string
     *
     * @ORM\Column(name="app_desc", type="string", nullable=true, options={"comment":"描述"})
     */
    private $app_desc;


    /**
     * Get wxExternalConfigId.
     *
     * @return int
     */
    public function getWxExternalConfigId()
    {
        return $this->wx_external_config_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return WxExternalConfig
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
     * Set appId.
     *
     * @param string $appId
     *
     * @return WxExternalConfig
     */
    public function setAppId($appId)
    {
        $this->app_id = $appId;

        return $this;
    }

    /**
     * Get appId.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * Set appName.
     *
     * @param string|null $appName
     *
     * @return WxExternalConfig
     */
    public function setAppName($appName = null)
    {
        $this->app_name = $appName;

        return $this;
    }

    /**
     * Get appName.
     *
     * @return string|null
     */
    public function getAppName()
    {
        return $this->app_name;
    }

    /**
     * Set appDesc.
     *
     * @param string|null $appDesc
     *
     * @return WxExternalConfig
     */
    public function setAppDesc($appDesc = null)
    {
        $this->app_desc = $appDesc;

        return $this;
    }

    /**
     * Get appDesc.
     *
     * @return string|null
     */
    public function getAppDesc()
    {
        return $this->app_desc;
    }
}
