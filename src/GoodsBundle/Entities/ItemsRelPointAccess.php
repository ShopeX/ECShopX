<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemsRelPointAccess 商品和积分获取设置关联表
 *
 * @ORM\Table(name="items_rel_point_access", options={"comment"="商品和积分获取设置关联表"}, indexes={
 *    @ORM\Index(name="ix_itemid_companyid", columns={"company_id","item_id"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsRelPointAccessRepository")
 */
class ItemsRelPointAccess
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品ID"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="point", type="bigint", options={"comment":"积分"})
     */
    private $point;

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
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;



    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return ItemsRelPointAccess
     */
    public function setItemId($itemId)
    {
        // XXX: review this code
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        // XXX: review this code
        return $this->item_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ItemsRelPointAccess
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
     * Set point.
     *
     * @param int $point
     *
     * @return ItemsRelPointAccess
     */
    public function setPoint($point)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point.
     *
     * @return int
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ItemsRelPointAccess
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
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
     * @return ItemsRelPointAccess
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
