<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemsRelCats 商品和分类关联表
 *
 * @ORM\Table(name="goods_items_rel_cats", options={"comment"="商品和分类关联表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_category_id", columns={"category_id"}),
 *    @ORM\Index(name="ix_created", columns={"created"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsRelCatsRepository")
 */
class ItemsRelCats
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
     * @ORM\Id
     * @ORM\Column(name="category_id", type="bigint", options={"comment":"商品分类id"})
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return ItemsRelCats
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     *
     * @return ItemsRelCats
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ItemsRelCats
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
     * Set created
     *
     * @param integer $created
     *
     * @return ItemsRelCats
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
     * @return ItemsRelCats
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
}
