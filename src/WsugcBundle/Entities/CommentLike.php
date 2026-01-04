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

//评论点赞
namespace WsugcBundle\Entities;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * WsugcPostBadge 评论点赞
 *
 * @ORM\Table(name="wsugc_comment_like", options={"comment"="评论点赞"}, indexes={
 *    @ORM\Index(name="idx_post_id", columns={"post_id"}),
 *    @ORM\Index(name="idx_comment_id", columns={"comment_id"}),
 *    @ORM\Index(name="idx_disabled", columns={"disabled"})
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\CommentLikeRepository")
 */
class CommentLike
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="comment_like_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $comment_like_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint", options={"comment":"笔记id"})
     */
    private $post_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="comment_id", type="bigint", options={"comment":"评论id"})
     */
    private $comment_id;

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
     * Get commentLikeId.
     *
     * @return int
     */
    public function getCommentLikeId()
    {
        return $this->comment_like_id;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return CommentLike
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
     * Set commentId.
     *
     * @param int $commentId
     *
     * @return CommentLike
     */
    public function setCommentId($commentId)
    {
        $this->comment_id = $commentId;

        return $this;
    }

    /**
     * Get commentId.
     *
     * @return int
     */
    public function getCommentId()
    {
        return $this->comment_id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CommentLike
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
     * @return CommentLike
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
     * @return CommentLike
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
     * @return CommentLike
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
