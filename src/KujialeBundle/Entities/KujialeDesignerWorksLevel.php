<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerWorksLevel
 *
 * @ORM\Table(name="kujiale_designer_works_level", indexes={@ORM\Index(name="idx_design_id", columns={"design_id"}), @ORM\Index(name="idx_plan_id", columns={"plan_id"})})
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerWorksLevelRepository")
 */
class KujialeDesignerWorksLevel
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
     * @ORM\Column(name="design_id", type="string", length=255, nullable=false, options={"comment":"方案id"})
     */
    private $design_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="plan_id", type="string", length=255, nullable=true, options={"comment":"户型ID"})
     */
    private $plan_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="spec_name", type="string", length=255, nullable=true, options={"comment":"户型的房型"})
     */
    private $spec_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="src_area", type="string", length=255, nullable=true, options={"comment":"户型的建筑面积"})
     */
    private $src_area;

    /**
     * @var string|null
     *
     * @ORM\Column(name="area", type="string", length=255, nullable=true, options={"comment":"户型的套内建筑面积"})
     */
    private $area;

    /**
     * @var string|null
     *
     * @ORM\Column(name="real_area", type="string", length=255, nullable=true, options={"comment":"户型的套内面积"})
     */
    private $real_area;

    /**
     * @var string|null
     *
     * @ORM\Column(name="plan_pic", type="text", length=65535, nullable=true, options={"comment":"户型图的URL"})
     */
    private $planPic;

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
     * Set designId.
     *
     * @param string $designId
     *
     * @return KujialeDesignerWorksLevel
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
     * Set planId.
     *
     * @param string|null $planId
     *
     * @return KujialeDesignerWorksLevel
     */
    public function setPlanId($planId = null)
    {
        $this->plan_id = $planId;

        return $this;
    }

    /**
     * Get planId.
     *
     * @return string|null
     */
    public function getPlanId()
    {
        return $this->plan_id;
    }

    /**
     * Set specName.
     *
     * @param string|null $specName
     *
     * @return KujialeDesignerWorksLevel
     */
    public function setSpecName($specName = null)
    {
        $this->spec_name = $specName;

        return $this;
    }

    /**
     * Get specName.
     *
     * @return string|null
     */
    public function getSpecName()
    {
        return $this->spec_name;
    }

    /**
     * Set srcArea.
     *
     * @param string|null $srcArea
     *
     * @return KujialeDesignerWorksLevel
     */
    public function setSrcArea($srcArea = null)
    {
        $this->src_area = $srcArea;

        return $this;
    }

    /**
     * Get srcArea.
     *
     * @return string|null
     */
    public function getSrcArea()
    {
        return $this->src_area;
    }

    /**
     * Set area.
     *
     * @param string|null $area
     *
     * @return KujialeDesignerWorksLevel
     */
    public function setArea($area = null)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get area.
     *
     * @return string|null
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Set realArea.
     *
     * @param string|null $realArea
     *
     * @return KujialeDesignerWorksLevel
     */
    public function setRealArea($realArea = null)
    {
        $this->real_area = $realArea;

        return $this;
    }

    /**
     * Get realArea.
     *
     * @return string|null
     */
    public function getRealArea()
    {
        return $this->real_area;
    }

    /**
     * Set planPic.
     *
     * @param string|null $planPic
     *
     * @return KujialeDesignerWorksLevel
     */
    public function setPlanPic($planPic = null)
    {
        $this->planPic = $planPic;

        return $this;
    }

    /**
     * Get planPic.
     *
     * @return string|null
     */
    public function getPlanPic()
    {
        return $this->planPic;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerWorksLevel
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
     * @return KujialeDesignerWorksLevel
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
