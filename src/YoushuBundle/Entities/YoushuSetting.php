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

namespace YoushuBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * youshu_setting 有数参数设置表
 *
 * @ORM\Table(name="youshu_setting", options={"comment":"有数参数设置表"},
 * indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="YoushuBundle\Repositories\YoushuSettingRepository")
 */
class YoushuSetting
{
    use Timestamps;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
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
     * @var string
     *
     * @ORM\Column(name="merchant_id", nullable=true, type="string", options={"comment":"merchant_id"})
     */
    private $merchant_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_id", nullable=true, type="string", options={"comment":"有数app_id，正式"})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_secret", nullable=true, type="string", options={"comment":"有数app_secret，正式"})
     */
    private $app_secret;

    /**
     * @var string
     *
     * @ORM\Column(name="api_url", nullable=true, type="string", options={"comment":"有数后端api url，正式"})
     */
    private $api_url;

    /**
     * @var string
     *
     * @ORM\Column(name="sandbox_app_id", nullable=true, type="string", options={"comment":"有数app_id，沙箱"})
     */
    private $sandbox_app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sandbox_app_secret", nullable=true, type="string", options={"comment":"有数app_secret，沙箱"})
     */
    private $sandbox_app_secret;

    /**
     * @var string
     *
     * @ORM\Column(name="sandbox_api_url", nullable=true, type="string", options={"comment":"有数后端api url，沙箱"})
     */
    private $sandbox_api_url;

    /**
     * @var string
     *
     * @ORM\Column(name="weapp_name", nullable=true, type="string", options={"comment":"小程序名称"})
     */
    private $weapp_name;

    /**
     * @var string
     *
     * @ORM\Column(name="weapp_app_id", nullable=true, type="string", options={"comment":"小程序app_id"})
     */
    private $weapp_app_id;


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
     * @return YoushuSetting
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
     * Set merchantId.
     *
     * @param string|null $merchantId
     *
     * @return YoushuSetting
     */
    public function setMerchantId($merchantId = null)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return string|null
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set appId.
     *
     * @param string|null $appId
     *
     * @return YoushuSetting
     */
    public function setAppId($appId = null)
    {
        $this->app_id = $appId;

        return $this;
    }

    /**
     * Get appId.
     *
     * @return string|null
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * Set appSecret.
     *
     * @param string|null $appSecret
     *
     * @return YoushuSetting
     */
    public function setAppSecret($appSecret = null)
    {
        $this->app_secret = $appSecret;

        return $this;
    }

    /**
     * Get appSecret.
     *
     * @return string|null
     */
    public function getAppSecret()
    {
        return $this->app_secret;
    }

    /**
     * Set sandboxAppId.
     *
     * @param string|null $sandboxAppId
     *
     * @return YoushuSetting
     */
    public function setSandboxAppId($sandboxAppId = null)
    {
        $this->sandbox_app_id = $sandboxAppId;

        return $this;
    }

    /**
     * Get sandboxAppId.
     *
     * @return string|null
     */
    public function getSandboxAppId()
    {
        return $this->sandbox_app_id;
    }

    /**
     * Set sandboxAppSecret.
     *
     * @param string|null $sandboxAppSecret
     *
     * @return YoushuSetting
     */
    public function setSandboxAppSecret($sandboxAppSecret = null)
    {
        $this->sandbox_app_secret = $sandboxAppSecret;

        return $this;
    }

    /**
     * Get sandboxAppSecret.
     *
     * @return string|null
     */
    public function getSandboxAppSecret()
    {
        return $this->sandbox_app_secret;
    }

    /**
     * Set weappName.
     *
     * @param string|null $weappName
     *
     * @return YoushuSetting
     */
    public function setWeappName($weappName = null)
    {
        $this->weapp_name = $weappName;

        return $this;
    }

    /**
     * Get weappName.
     *
     * @return string|null
     */
    public function getWeappName()
    {
        return $this->weapp_name;
    }

    /**
     * Set weappAppId.
     *
     * @param string|null $weappAppId
     *
     * @return YoushuSetting
     */
    public function setWeappAppId($weappAppId = null)
    {
        $this->weapp_app_id = $weappAppId;

        return $this;
    }

    /**
     * Get weappAppId.
     *
     * @return string|null
     */
    public function getWeappAppId()
    {
        return $this->weapp_app_id;
    }

    /**
     * Set apiUrl.
     *
     * @param string|null $apiUrl
     *
     * @return YoushuSetting
     */
    public function setApiUrl($apiUrl = null)
    {
        $this->api_url = $apiUrl;

        return $this;
    }

    /**
     * Get apiUrl.
     *
     * @return string|null
     */
    public function getApiUrl()
    {
        return $this->api_url;
    }

    /**
     * Set sandboxApiUrl.
     *
     * @param string|null $sandboxApiUrl
     *
     * @return YoushuSetting
     */
    public function setSandboxApiUrl($sandboxApiUrl = null)
    {
        $this->sandbox_api_url = $sandboxApiUrl;

        return $this;
    }

    /**
     * Get sandboxApiUrl.
     *
     * @return string|null
     */
    public function getSandboxApiUrl()
    {
        return $this->sandbox_api_url;
    }
}
