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

namespace CommunityBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CommunityCart 社区团购购物车
 *
 * @ORM\Table(name="community_cart", options={"comment"="社区团购购物车"})
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityCartRepository")
 */
class CommunityCart
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="cart_id", type="bigint", options={"comment":"购物车ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $cart_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="chief_id", type="bigint", options={"comment":"团长id"})
     */
    private $chief_id;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动id"})
     */
    private $activity_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="num", type="bigint", options={"comment":"商品数量", "default" : 1})
     */
    private $num = 1;

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
     * Get cartId
     *
     * @return integer
     */
    public function getCartId()
    {
        return $this->cart_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return CommunityCart
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
     * @return CommunityCart
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
     * Set chiefId
     *
     * @param integer $chiefId
     *
     * @return CommunityCart
     */
    public function setChiefId($chiefId)
    {
        $this->chief_id = $chiefId;

        return $this;
    }

    /**
     * Get chiefId
     *
     * @return integer
     */
    public function getChiefId()
    {
        return $this->chief_id;
    }

    /**
     * Set activityId
     *
     * @param integer $activityId
     *
     * @return CommunityCart
     */
    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId
     *
     * @return integer
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return CommunityCart
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
     * Set num
     *
     * @param integer $num
     *
     * @return CommunityCart
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return CommunityCart
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
     * @return CommunityCart
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
