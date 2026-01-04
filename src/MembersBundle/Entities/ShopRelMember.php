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
 * ShopRelMember 店铺管理会员表
 *
 * @ORM\Table(name="members_rel_shop", options={"comment"="店铺管理会员表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_shop_id",     columns={"shop_id"}),
 *    @ORM\Index(name="idx_user_id",   columns={"user_id"})
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\ShopRelMemberRepository")
 */
class ShopRelMember
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", options={"comment"="店铺id"})
     */
    private $shop_id;

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
     * @var string
     *
     * shop 服务网点
     * distributor 店铺
     *
     * @ORM\Column(name="shop_type", type="string", length=15, options={"comment":"店铺类型", "default":"shop"})
     */
    private $shop_type = "shop";

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ShopRelMember
     */
    public function setCompanyId($companyId)
    {
        // Ref: 1996368445
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
     * @return ShopRelMember
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return ShopRelMember
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ShopRelMember
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
     * @return ShopRelMember
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set shopType.
     *
     * @param string $shopType
     *
     * @return ShopRelMember
     */
    public function setShopType($shopType)
    {
        $this->shop_type = $shopType;

        return $this;
    }

    /**
     * Get shopType.
     *
     * @return string
     */
    public function getShopType()
    {
        return $this->shop_type;
    }
}
