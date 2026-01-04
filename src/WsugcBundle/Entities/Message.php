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

//站内消息，通知
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WsugcMessage
 *
 * @ORM\Table(name="wsugc_message",options={"comment"="消息"}, indexes={
 *    @ORM\Index(name="idx_to_user_id", columns={"to_user_id"}),
 *    @ORM\Index(name="idx_type", columns={"type"}),
 *    @ORM\Index(name="idx_post_id", columns={"post_id"}),
 *    @ORM\Index(name="idx_comment_id", columns={"comment_id"}),
 *    @ORM\Index(name="idx_hasread", columns={"hasread"}),
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\MessageRepository")
 *
 */
class Message
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="message_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $message_id;


    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=false, options={"comment"="消息类型.system系统消息;replyPost回复消息likePost笔记点赞 favoritePost收藏笔记 followerUser关注了您"})
     */
    private $type;


   /**
     * @var string
     *
     * @ORM\Column(name="sub_type", type="string", nullable=false, options={"comment"="消息类型及子类型.系统消息(system):approve通过,reject拒绝,unenable下架; 回复消息(reply):replyPost评论了您的笔记,replyComment回复了您的评论; 收藏笔记(favoritePost):favorite收藏,unfavorite取消收藏; 关注(followerUser):follow关注,unfollow取关;  点赞(like):likePost点赞笔记,likeComment点赞评论"})
     */
    private $sub_type;


    /**
     * @var source
     *
     * @ORM\Column(name="source", type="integer",nullable=true,options={"comment":"来源 1用户,2官方(系统通知)", "default": "1"})
     */
    private $source;

    /**
     * @var integer
     *
     * @ORM\Column(name="from_user_id", type="bigint",options={"comment":"来自用户", "default": 0})
     */
    private $from_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="from_nickname", type="string",nullable=true,options={"comment":"来自昵称", "default": ""})
     */
    private $from_nickname;

    /**
     * @var integer
     *
     * @ORM\Column(name="to_user_id", type="bigint",options={"comment":"发给用户", "default": 0})
     */
    private $to_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="to_nickname", type="string",nullable=true,options={"comment":"发给昵称", "default": ""})
     */
    private $to_nickname;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint",options={"comment":"笔记id", "default": 0})
     */
    private $post_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="comment_id", type="bigint",nullable=true,options={"comment":"评论id", "default": 0})
     */
    private $comment_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true, options={"comment"="通知标题"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255, nullable=true, options={"comment"="通知内容"})
     */
    private $content;

       /**
     * @var int
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="integer", nullable=false, options={"unsigned"=true,"comment"="添加时间"})
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;


    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hasread", type="boolean", options={"comment":"是否已读.", "default": false})
     */
    private $hasread = false;

   
     /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return YuyueActivity
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
        // 53686f704578
        return $this->company_id;
    }

    /**
     * Get messageId.
     *
     * @return int
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Message
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set subType.
     *
     * @param string $subType
     *
     * @return Message
     */
    public function setSubType($subType)
    {
        $this->sub_type = $subType;

        return $this;
    }

    /**
     * Get subType.
     *
     * @return string
     */
    public function getSubType()
    {
        return $this->sub_type;
    }

    /**
     * Set source.
     *
     * @param int|null $source
     *
     * @return Message
     */
    public function setSource($source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return int|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set fromUserId.
     *
     * @param int $fromUserId
     *
     * @return Message
     */
    public function setFromUserId($fromUserId)
    {
        $this->from_user_id = $fromUserId;

        return $this;
    }

    /**
     * Get fromUserId.
     *
     * @return int
     */
    public function getFromUserId()
    {
        return $this->from_user_id;
    }

    /**
     * Set fromNickname.
     *
     * @param string $fromNickname
     *
     * @return Message
     */
    public function setFromNickname($fromNickname)
    {
        $this->from_nickname = $fromNickname;

        return $this;
    }

    /**
     * Get fromNickname.
     *
     * @return string
     */
    public function getFromNickname()
    {
        return $this->from_nickname;
    }

    /**
     * Set toUserId.
     *
     * @param int $toUserId
     *
     * @return Message
     */
    public function setToUserId($toUserId)
    {
        $this->to_user_id = $toUserId;

        return $this;
    }

    /**
     * Get toUserId.
     *
     * @return int
     */
    public function getToUserId()
    {
        return $this->to_user_id;
    }

    /**
     * Set toNickname.
     *
     * @param string $toNickname
     *
     * @return Message
     */
    public function setToNickname($toNickname)
    {
        $this->to_nickname = $toNickname;

        return $this;
    }

    /**
     * Get toNickname.
     *
     * @return string
     */
    public function getToNickname()
    {
        return $this->to_nickname;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return Message
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
     * @return Message
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
     * Set title.
     *
     * @param string $title
     *
     * @return Message
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Message
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Message
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
     * @return Message
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

    /**
     * Set hasread.
     *
     * @param bool $hasread
     *
     * @return Message
     */
    public function setHasread($hasread)
    {
        $this->hasread = $hasread;

        return $this;
    }

    /**
     * Get hasread.
     *
     * @return bool
     */
    public function getHasread()
    {
        return $this->hasread;
    }
}
