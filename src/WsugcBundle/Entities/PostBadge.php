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

//笔记角标
namespace WsugcBundle\Entities;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * WsugcPostBadge 笔记的角标
 *
 * @ORM\Table(name="wsugc_post_badge", options={"comment"="笔记角标"}, indexes={
 *    @ORM\Index(name="idx_post_id", columns={"post_id"}),
 *    @ORM\Index(name="idx_badge_id", columns={"badge_id"})
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\PostBadgeRepository")
 */
class PostBadge
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="post_badge_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $post_badge_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint", options={"comment":"笔记id"})
     */
    private $post_id;
   
    /**
     * @var integer
     *
     * @ORM\Column(name="badge_id", type="bigint", options={"comment":"角标id"})
     */
    private $badge_id;
  /**
     * @var int
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="integer", nullable=false, options={"unsigned"=true,"comment"="添加时间"})
     */
    private $created;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * Get postBadgeId.
     *
     * @return int
     */
    public function getPostBadgeId()
    {
        return $this->post_badge_id;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return WsugcPostBadge
     */
    public function setPostId($postId)
    {
        $this->post_id = $postId;

        return $this;
    }

    /**
     * Get postId.
     *
     * @return int
     */
    public function getPostId()
    {
        return $this->post_id;
    }

    /**
     * Set badgeId.
     *
     * @param int $badgeId
     *
     * @return WsugcPostBadge
     */
    public function setBadgeId($badgeId)
    {
        $this->badge_id = $badgeId;

        return $this;
    }

    /**
     * Get badgeId.
     *
     * @return int
     */
    public function getBadgeId()
    {
        return $this->badge_id;
    }
        /**
     * Set created.
     *
     * @param int $created
     *
     * @return Tag
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PostTopic
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
}
