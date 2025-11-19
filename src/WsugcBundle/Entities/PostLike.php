<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

//笔记点赞
namespace WsugcBundle\Entities;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * WsugcPostBadge 笔记点赞
 *
 * @ORM\Table(name="wsugc_post_like", options={"comment"="笔记点赞"}, indexes={
 *    @ORM\Index(name="idx_post_id", columns={"post_id"}),
 *    @ORM\Index(name="idx_disabled", columns={"disabled"})
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\PostLikeRepository")
 */
class PostLike
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="post_like_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $post_like_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint", options={"comment":"笔记id"})
     */
    private $post_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员id"})
     */
    private $user_id;
   
    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"是否无效", "default": false})
     */
    private $disabled;

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
     * Get postLikeId.
     *
     * @return int
     */
    public function getPostLikeId()
    {
        return $this->post_like_id;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return PostLike
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return PostLike
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
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return PostLike
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
     * Set created.
     *
     * @param int $created
     *
     * @return PostLike
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
     * @return PostLike
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
