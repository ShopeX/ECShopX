<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerGoodsRel
 *
 * @ORM\Table(name="kujiale_designer_goods_rel", indexes={@ORM\Index(name="idx_brand_good_id", columns={"obs_brand_good_id"}), @ORM\Index(name="idx_pic_id", columns={"pic_id"})})
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerGoodsRelRepository")
 */
class KujialeDesignerGoodsRel
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
     * @var string
     *
     * @ORM\Column(name="pic_id", type="string", length=255, nullable=false, options={"comment":"渲染图ID"})
     */
    private $pic_id;

    /**
     * @var string
     *
     * @ORM\Column(name="obs_brand_good_id", type="string", length=128, nullable=false, options={"comment":"商品ID"})
     */
    private $obs_brand_good_id = '';

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
     * Set picId.
     *
     * @param string $picId
     *
     * @return KujialeDesignerGoodsRel
     */
    public function setPicId($picId)
    {
        $this->pic_id = $picId;

        return $this;
    }

    /**
     * Get picId.
     *
     * @return string
     */
    public function getPicId()
    {
        return $this->pic_id;
    }

    /**
     * Set obsBrandGoodId.
     *
     * @param string $obsBrandGoodId
     *
     * @return KujialeDesignerGoodsRel
     */
    public function setObsBrandGoodId($obsBrandGoodId)
    {
        $this->obs_brand_good_id = $obsBrandGoodId;

        return $this;
    }

    /**
     * Get obsBrandGoodId.
     *
     * @return string
     */
    public function getObsBrandGoodId()
    {
        return $this->obs_brand_good_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerGoodsRel
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
     * @return KujialeDesignerGoodsRel
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
