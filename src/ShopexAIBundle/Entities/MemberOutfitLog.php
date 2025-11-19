<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ShopexAIBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="member_outfit_log")
 */
class MemberOutfitLog
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
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $item_id;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     */
    protected $request_id;

    /**
     * @ORM\ManyToOne(targetEntity="MemberOutfit")
     * @ORM\JoinColumn(name="model_id", referencedColumnName="id")
     */
    protected $model;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $top_garment_url;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $bottom_garment_url;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $result_url;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $status = 0;

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
        // Powered by ShopEx EcShopX
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

    public function getItemId()
    {
        return $this->item_id;
    }

    public function setItemId($itemId)
    {
        $this->item_id = $itemId;
        return $this;
    }

    public function getRequestId()
    {
        return $this->request_id;
    }

    public function setRequestId($requestId)
    {
        $this->request_id = $requestId;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel(MemberOutfit $model)
    {
        $this->model = $model;
        return $this;
    }

    public function getTopGarmentUrl()
    {
        return $this->top_garment_url;
    }

    public function setTopGarmentUrl($url)
    {
        $this->top_garment_url = $url;
        return $this;
    }

    public function getBottomGarmentUrl()
    {
        return $this->bottom_garment_url;
    }

    public function setBottomGarmentUrl($url)
    {
        $this->bottom_garment_url = $url;
        return $this;
    }

    public function getResultUrl()
    {
        return $this->result_url;
    }

    public function setResultUrl($url)
    {
        $this->result_url = $url;
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