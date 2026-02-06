<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerWorksItemRel
 * 设计师作品与商品绑定关系表
 *
 * @ORM\Table(name="kujiale_designer_works_item_rel", 
 *     indexes={
 *         @ORM\Index(name="idx_item_id", columns={"item_id"}),
 *         @ORM\Index(name="idx_design_id", columns={"design_id"}),
 *         @ORM\Index(name="idx_goods_bn", columns={"goods_bn"})
 *     },
 *     options={"comment":"设计师作品与商品绑定关系表"}
 * )
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerWorksItemRelRepository")
 */
class KujialeDesignerWorksItemRel
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="item_id", type="bigint", nullable=false, options={"comment":"商品ID"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="design_id", type="string", length=255, nullable=false, options={"comment":"设计ID"})
     */
    private $design_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="goods_bn", type="string", length=255, nullable=true, options={"comment":"SPU货号"})
     */
    private $goods_bn;

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="integer", nullable=false, options={"comment":"创建时间"})
     */
    private $created;

    /**
     * @var int|null
     *
     * @ORM\Column(name="updated", type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $updated;

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
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return KujialeDesignerWorksItemRel
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
     * Set designId.
     *
     * @param string $designId
     *
     * @return KujialeDesignerWorksItemRel
     */
    public function setDesignId($designId)
    {
        $this->design_id = $designId;

        return $this;
    }

    /**
     * Get designId.
     *
     * @return string
     */
    public function getDesignId()
    {
        return $this->design_id;
    }

    /**
     * Set goodsBn.
     *
     * @param string|null $goodsBn
     *
     * @return KujialeDesignerWorksItemRel
     */
    public function setGoodsBn($goodsBn = null)
    {
        $this->goods_bn = $goodsBn;

        return $this;
    }

    /**
     * Get goodsBn.
     *
     * @return string|null
     */
    public function getGoodsBn()
    {
        return $this->goods_bn;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerWorksItemRel
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
     * @return KujialeDesignerWorksItemRel
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
