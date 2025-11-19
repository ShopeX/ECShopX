<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * WxExternalRoutes 外部小程序路径表
 *
 * @ORM\Table(name="wx_external_routes", options={"comment":"外部小程序路径表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\WxExternalRoutesRepository")
 */

class WxExternalRoutes
{
    use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="wx_external_routes_id", type="bigint", options={"comment":"外部小程序路径表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $wx_external_routes_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="wx_external_config_id", type="bigint", options={"comment":"外部小程序配置表id"})
     */
    private $wx_external_config_id;

    /**
     * @var string
     *
     * @ORM\Column(name="route_name", type="string", options={"comment":"页面名称"})
     */
    private $route_name;

    /**
     * @var string
     *
     * @ORM\Column(name="route_info", type="string", options={"comment":"页面路径"})
     */
    private $route_info;

    /**
     * @var string
     *
     * @ORM\Column(name="route_desc", type="string", nullable=true, options={"comment":"描述"})
     */
    private $route_desc;


    /**
     * Get wxExternalRoutesId.
     *
     * @return int
     */
    public function getWxExternalRoutesId()
    {
        return $this->wx_external_routes_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return WxExternalRoutes
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
     * Set wxExternalConfigId.
     *
     * @param int $wxExternalConfigId
     *
     * @return WxExternalRoutes
     */
    public function setWxExternalConfigId($wxExternalConfigId)
    {
        $this->wx_external_config_id = $wxExternalConfigId;

        return $this;
    }

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
     * Set routeName.
     *
     * @param string $routeName
     *
     * @return WxExternalRoutes
     */
    public function setRouteName($routeName)
    {
        $this->route_name = $routeName;

        return $this;
    }

    /**
     * Get routeName.
     *
     * @return string
     */
    public function getRouteName()
    {
        return $this->route_name;
    }

    /**
     * Set routeInfo.
     *
     * @param string $routeInfo
     *
     * @return WxExternalRoutes
     */
    public function setRouteInfo($routeInfo)
    {
        $this->route_info = $routeInfo;

        return $this;
    }

    /**
     * Get routeInfo.
     *
     * @return string
     */
    public function getRouteInfo()
    {
        return $this->route_info;
    }

    /**
     * Set routeDesc.
     *
     * @param string|null $routeDesc
     *
     * @return WxExternalRoutes
     */
    public function setRouteDesc($routeDesc = null)
    {
        $this->route_desc = $routeDesc;

        return $this;
    }

    /**
     * Get routeDesc.
     *
     * @return string|null
     */
    public function getRouteDesc()
    {
        return $this->route_desc;
    }
}
