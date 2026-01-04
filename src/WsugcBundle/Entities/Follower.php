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

//关注-用户/粉丝

namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Follower
 *
 * @ORM\Table(name="wsugc_follower")
 * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\FollowerRepository")
 */
class Follower
{
    /**
     * @var int
     *
     * @ORM\Column(name="follower_id", type="integer", nullable=false, options={"unsigned"=true,"comment"="id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $follower_id;


    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="会员id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="follower_user_id", type="bigint", options={"comment"="粉丝会员id"})
     */
    private $follower_user_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"是否无效", "default": false})
     */
    private $disabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

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
    private $updated;


    /**
     * Get followerId.
     *
     * @return int
     */
    public function getFollowerId()
    {
        return $this->follower_id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return Follower
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set followerUserId.
     *
     * @param int $followerUserId
     *
     * @return Follower
     */
    public function setFollowerUserId($followerUserId)
    {
        $this->follower_user_id = $followerUserId;

        return $this;
    }

    /**
     * Get followerUserId.
     *
     * @return int
     */
    public function getFollowerUserId()
    {
        return $this->follower_user_id;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return Follower
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Follower
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Follower
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
     * @param int $updated
     *
     * @return Follower
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
