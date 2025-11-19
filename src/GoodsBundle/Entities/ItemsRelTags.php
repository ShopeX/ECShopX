<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemRelTags 商品关联标签表
 *
 * @ORM\Table(name="items_rel_tags", options={"comment"="商品关联标签表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_item_id", columns={"item_id"}),
 *    @ORM\Index(name="ix_tag_id", columns={"tag_id"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsRelTagsRepository")
 */
class ItemsRelTags
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="tag_id", type="bigint", options={"comment"="标签id"})
     */
    private $tag_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment"="商品id"})
     */
    private $item_id;

    /**
     * Set tagId
     *
     * @param integer $tagId
     *
     * @return ItemsRelTags
     */
    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId
     *
     * @return integer
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ItemsRelTags
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return ItemsRelTags
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
}
