<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Printer 打印表
 *
 * @ORM\Table(name="espier_printer", options={"comment":"打印机表"})
 * @ORM\Entity(repositoryClass="EspierBundle\Repositories\PrinterRepository")
 */

class Printer
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"公司id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", options={"comment":"打印机名称"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", options={"comment":"打印机类型 yilianyun 易连云"})
     */
    protected $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_id", type="string", options={"comment":"关联店铺"})
     */
    protected $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_terminal", type="string", options={"comment":"打印机终端号"})
     */
    protected $app_terminal;

    /**
     * @var string
     *
     * @ORM\Column(name="app_key", type="string", options={"comment":"打印机秘钥"})
     */
    protected $app_key;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return Printer
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Printer
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Printer
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Printer
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
     * Set distributorId
     *
     * @param string $distributorId
     *
     * @return Printer
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return string
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set appTerminal
     *
     * @param string $appTerminal
     *
     * @return Printer
     */
    public function setAppTerminal($appTerminal)
    {
        $this->app_terminal = $appTerminal;

        return $this;
    }

    /**
     * Get appTerminal
     *
     * @return string
     */
    public function getAppTerminal()
    {
        return $this->app_terminal;
    }

    /**
     * Set appKey
     *
     * @param string $appKey
     *
     * @return Printer
     */
    public function setAppKey($appKey)
    {
        $this->app_key = $appKey;

        return $this;
    }

    /**
     * Get appKey
     *
     * @return string
     */
    public function getAppKey()
    {
        return $this->app_key;
    }
}
