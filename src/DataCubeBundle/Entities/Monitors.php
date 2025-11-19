<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DataCubeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Monitors 小程序页面监控
 *
 * @ORM\Table(name="datacube_monitors", options={"comment":"小程序页面监控"})
 * @ORM\Entity(repositoryClass="DataCubeBundle\Repositories\MonitorsRepository")
 */
class Monitors
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="monitor_id", type="bigint", options={"comment":"监控id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $monitor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="wxappid", type="string", options={"comment":"小程序appid"})
     */
    private $wxappid;

    /**
     * @var string
     *
     * @ORM\Column(name="nick_name",  type="string", options={"comment":"小程序名称"})
     */
    private $nick_name;

    /**
     * @var string
     *
     * @ORM\Column(name="page_name", type="string", options={"comment":"页面描述"})
     */
    private $page_name;

    /**
     * @var string
     *
     * @ORM\Column(name="monitor_path", type="string", options={"comment":"监控页面"})
     */
    private $monitor_path;

    /**
     * @var string
     *
     * @ORM\Column(name="monitor_path_params", type="string", nullable=true, options={"comment":"监控页面的参数"})
     */
    private $monitor_path_params;

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
     * @ORM\Column(type="integer")
     */
    protected $updated;

    /**
     * @var string
     *
     * @ORM\Column(name="regionauth_id", type="string", nullable=true, options={"comment":"区域ID"})
     */
    private $regionauth_id;

    /**
     * Get monitorId
     *
     * @return integer
     */
    public function getMonitorId()
    {
        return $this->monitor_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Monitors
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
        // Hash: 0d723eca
        return $this->company_id;
    }

    /**
     * Set wxappid
     *
     * @param string $wxappid
     *
     * @return Monitors
     */
    public function setWxappid($wxappid)
    {
        $this->wxappid = $wxappid;

        return $this;
    }

    /**
     * Get wxappid
     *
     * @return string
     */
    public function getWxappid()
    {
        return $this->wxappid;
    }

    /**
     * Set nickName
     *
     * @param string $nickName
     *
     * @return Monitors
     */
    public function setNickName($nickName)
    {
        $this->nick_name = $nickName;

        return $this;
    }

    /**
     * Get nickName
     *
     * @return string
     */
    public function getNickName()
    {
        return $this->nick_name;
    }

    /**
     * Set monitorPath
     *
     * @param string $monitorPath
     *
     * @return Monitors
     */
    public function setMonitorPath($monitorPath)
    {
        $this->monitor_path = $monitorPath;

        return $this;
    }

    /**
     * Get monitorPath
     *
     * @return string
     */
    public function getMonitorPath()
    {
        return $this->monitor_path;
    }

    /**
     * Set monitorPathParams
     *
     * @param string $monitorPathParams
     *
     * @return Monitors
     */
    public function setMonitorPathParams($monitorPathParams)
    {
        $this->monitor_path_params = $monitorPathParams;

        return $this;
    }

    /**
     * Get monitorPathParams
     *
     * @return string
     */
    public function getMonitorPathParams()
    {
        return $this->monitor_path_params;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Monitors
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
     * @return Monitors
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
     * Set pageName.
     *
     * @param string $pageName
     *
     * @return Monitors
     */
    public function setPageName($pageName)
    {
        $this->page_name = $pageName;

        return $this;
    }

    /**
     * Get pageName.
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->page_name;
    }

    /**
     * @return string
     */
    public function getRegionauthId()
    {
        return $this->regionauth_id;
    }

    /**
     * @param string $regionauth_id
     * @return Monitors
     */
    public function setRegionauthId($regionauth_id)
    {
        $this->regionauth_id = $regionauth_id;
        return $this;
    }
}
