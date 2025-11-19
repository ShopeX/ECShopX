<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
