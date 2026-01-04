<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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