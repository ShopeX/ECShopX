<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerWorksLike
 *
 * @ORM\Table(name="kujiale_designer_works_like", indexes={@ORM\Index(name="idx_design_id", columns={"design_id"}), @ORM\Index(name="idx_user_id", columns={"user_id"}), @ORM\Index(name="idx_plan_id", columns={"plan_id"})})
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerWorksLikeRepository")
 */
class KujialeDesignerWorksLike
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
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true, options={"comment":"点赞用户id"})
     */
    private $user_id;

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
     * Set PicId.
     *
     * @param string $picId
     *
     * @return KujialeDesignerWorksPic
     */
    public function setPicId($picId)
    {
        $this->pic_id = $picId;

        return $this;
    }

    /**
     * Set designId.
     *
     * @param string|null $designId
     *
     * @return KujialeDesignerWorksPic
     */
    public function setDesignId($designId = null)
    {
        $this->design_id = $designId;

        return $this;
    }

    /**
     * Get designId.
     *
     * @return string|null
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
     * @return KujialeDesignerWorksPic
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
     * Set UserId.
     *
     * @param string|null $userId
     *
     * @return KujialeDesignerWorksPic
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get UserId.
     *
     * @return string|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerWorksPic
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
     * @return KujialeDesignerWorksPic
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
