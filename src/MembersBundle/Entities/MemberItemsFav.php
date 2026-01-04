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

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MemberItemsFav 会员收藏商品表
 *
 * @ORM\Table(name="members_items_fav", options={"comment"="会员收藏商品表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_user_id",   columns={"user_id"})
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberItemsFavRepository")
 */
class MemberItemsFav
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="fav_id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $fav_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="会员id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment"="商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", length=100, options={"comment"="商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="item_image", type="text", options={"comment"="商品图片"})
     */
    private $item_image;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_price", type="integer", options={"comment":"价格,单位为‘分’"})
     */
    private $item_price;

    /**
     * @var string
     *
     * @ORM\Column(name="item_type", type="string", length=15, options={"comment":"商品类型，normal: 普通商品 pointsmall:积分商城", "default": "normal"})
     */
    private $item_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="point", nullable=true, type="integer", options={"comment":"积分兑换价格,item_type=pointsmall时必须", "default": 0})
     */
    private $point;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * Get favId
     *
     * @return integer
     */
    public function getFavId()
    {
        return $this->fav_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberItemsFav
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return MemberItemsFav
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return MemberItemsFav
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
     * Set itemName
     *
     * @param string $itemName
     *
     * @return MemberItemsFav
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set itemImage
     *
     * @param string $itemImage
     *
     * @return MemberItemsFav
     */
    public function setItemImage($itemImage)
    {
        $this->item_image = $itemImage;

        return $this;
    }

    /**
     * Get itemImage
     *
     * @return string
     */
    public function getItemImage()
    {
        return $this->item_image;
    }

    /**
     * Set itemPrice
     *
     * @param string $itemPrice
     *
     * @return MemberItemsFav
     */
    public function setItemPrice($itemPrice)
    {
        $this->item_price = $itemPrice;

        return $this;
    }

    /**
     * Get itemPrice
     *
     * @return string
     */
    public function getItemPrice()
    {
        return $this->item_price;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MemberItemsFav
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
     * @return MemberItemsFav
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


    /**
     * Set itemType.
     *
     * @param string $itemType
     *
     * @return MemberItemsFav
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get itemType.
     *
     * @return string
     */
    public function getItemType()
    {
        return $this->item_type;
    }

    /**
     * Set point.
     *
     * @param int|null $point
     *
     * @return MemberItemsFav
     */
    public function setPoint($point = null)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point.
     *
     * @return int|null
     */
    public function getPoint()
    {
        return $this->point;
    }
}
