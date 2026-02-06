<?php

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemsGroupRelItem 商品分组关联商品
 *
 * @ORM\Table(name="items_group_rel_item", options={"comment"="商品分组关联商品"}, indexes={
 *    @ORM\Index(name="idx_goods_id", columns={"goods_id"}),
 *    @ORM\Index(name="idx_group_id", columns={"group_id"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsGroupRelItemRepository")
 */
class ItemsGroupRelItem
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID", "default":0})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="regionauth_id", type="bigint", options={"comment":"地区ID", "default":0})
     */
    private $regionauth_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="bigint", options={"comment":"分组ID", "default":0})
     */
    private $group_id = 0;

    /**
     * @var string
     *
     * 分组类型(coupon, widget, marketing)
     * 
     * @ORM\Column(name="group_type", type="string", length=50, options={"comment"="分组类型(coupon, widget, marketing)", "default":""})
     */
    private $group_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment"="sku-id", "default":0})
     */
    private $item_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", options={"comment"="spu-id", "default":0})
     */
    private $goods_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_del", type="bigint", options={"comment"="是否删除", "default":0})
     */
    private $is_del = 0;

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
     * @return ItemsGroupRelItem
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
     * Set regionauthId.
     *
     * @param int $regionauthId
     *
     * @return ItemsGroupRelItem
     */
    public function setRegionauthId($regionauthId)
    {
        $this->regionauth_id = $regionauthId;

        return $this;
    }

    /**
     * Get regionauthId.
     *
     * @return int
     */
    public function getRegionauthId()
    {
        return $this->regionauth_id;
    }

    /**
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return ItemsGroupRelItem
     */
    public function setGroupId($groupId)
    {
        $this->group_id = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return ItemsGroupRelItem
     */
    public function setItemId($itemId)
    {
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
        return $this->item_id;
    }

    /**
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return ItemsGroupRelItem
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set isDel.
     *
     * @param int $isDel
     *
     * @return ItemsGroupRelItem
     */
    public function setIsDel($isDel)
    {
        $this->is_del = $isDel;

        return $this;
    }

    /**
     * Get isDel.
     *
     * @return int
     */
    public function getIsDel()
    {
        return $this->is_del;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ItemsGroupRelItem
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
     * @return ItemsGroupRelItem
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

    /**
     * Set groupType.
     *
     * @param string $groupType
     *
     * @return ItemsGroupRelItem
     */
    public function setGroupType($groupType)
    {
        $this->group_type = $groupType;

        return $this;
    }

    /**
     * Get groupType.
     *
     * @return string
     */
    public function getGroupType()
    {
        return $this->group_type;
    }
}

