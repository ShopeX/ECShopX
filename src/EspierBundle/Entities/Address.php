<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Address 地区表
 *
 * @ORM\Table(name="espier_address", options={"comment":"地区表"}, indexes={
 *    @ORM\Index(name="ix_parent_id", columns={"parent_id"}),
 *    @ORM\Index(name="ix_label", columns={"label"}),
 * })
 * @ORM\Entity(repositoryClass="EspierBundle\Repositories\AddressRepository")
 */

class Address
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"地区id"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", options={"comment":"地区名称"})
     */
    protected $label;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="parent_id", type="bigint", options={"comment":"父级id"})
     */
    protected $parent_id;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", options={"comment":"路径"})
     */
    protected $path;


    /**
     * Set id
     *
     * @param integer $id
     *
     * @return Address
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
     * Set label
     *
     * @param string $label
     *
     * @return Address
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     *
     * @return Address
     */
    public function setParentId($parentId)
    {
        $this->parent_id = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return Address
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
