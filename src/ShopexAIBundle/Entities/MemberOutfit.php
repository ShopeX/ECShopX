<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ShopexAIBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="member_outfit")
 */
class MemberOutfit
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $member_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $model_image;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $status = 1;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMemberId()
    {
        return $this->member_id;
    }

    public function setMemberId($memberId)
    {
        $this->member_id = $memberId;
        return $this;
    }

    public function getModelImage()
    {
        return $this->model_image;
    }

    public function setModelImage($modelImage)
    {
        $this->model_image = $modelImage;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function setUpdatedAt()
    {
        $this->updated_at = new \DateTime();
        return $this;
    }
} 