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

//笔记-评论
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Comment 笔记-评论
 *
 * @ORM\Table(name="wsugc_comment", options={"comment"="笔记评论"}, indexes={
 *    
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\CommentRepository")
 */
class Comment
{
     /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="comment_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $comment_id;

   /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint", options={"comment":"笔记id"})
     */
    private $post_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_comment_id", type="bigint", options={"comment":"父级评论id"})
     */
    private $parent_comment_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="reply_comment_id", type="bigint", options={"comment":"回复评论id"})
     */
    private $reply_comment_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="reply_user_id", type="bigint", options={"comment":"回复会员id"})
     */
    private $reply_user_id;

    /**
     * @var longtext
     *
     * @ORM\Column(name="content", type="text", nullable=true,options={"comment":"内容","default":""})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="likes", type="string", options={"comment":"点赞数"})
     */
    private $likes = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string",nullable=true, options={"comment":"ip"})
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="province", type="string",nullable=true, options={"comment":"省份"})
     */
    private $province;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string",nullable=true, options={"comment":"城市"})
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="district", type="string",nullable=true, options={"comment":"区县"})
     */
    private $district;

    /**
     * @var integer
     *
     * @ORM\Column(name="p_order", type="integer", nullable=true,options={"comment":"排序", "default": 0})
     */
    private $p_order = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"审核状态(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)", "default": 0})
     */
    private $status = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="enable", type="integer", options={"comment":"上架状态(0下架,1上架)", "default": 1})
     */
    private $enable = '1';

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
     * @var \DateTime $ai_verify_time
     *
     * @ORM\Column(name="ai_verify_time", type="bigint", nullable=true,options={"comment":"机器审核时间","default":"0"})
     */
    protected $ai_verify_time;

    /**
     * @var \DateTime $manual_verify_time
     *
     * @ORM\Column(name="manual_verify_time", type="bigint", nullable=true,options={"comment":"人工审核时间","default":"0"})
     */
    protected $manual_verify_time;

    /**
     * @var string
     *
     * @ORM\Column(name="ai_refuse_reason", type="string",nullable=true, options={"comment":"机器拒绝理由"})
     */
    private $ai_refuse_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="manual_refuse_reason", type="string",nullable=true, options={"comment":"人工拒绝理由"})
     */
    private $manual_refuse_reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;


   

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
     * Set postId.
     *
     * @param int $postId
     *
     * @return Comment
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
     * @return Comment
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
     * Set parentCommentId.
     *
     * @param int $parentCommentId
     *
     * @return Comment
     */
    public function setParentCommentId($parentCommentId)
    {
        $this->parent_comment_id = $parentCommentId;

        return $this;
    }

    /**
     * Get parentCommentId.
     *
     * @return int
     */
    public function getParentCommentId()
    {
        return $this->parent_comment_id;
    }

    /**
     * Set replyCommentId.
     *
     * @param int $replyCommentId
     *
     * @return Comment
     */
    public function setReplyCommentId($replyCommentId)
    {
        $this->reply_comment_id = $replyCommentId;

        return $this;
    }

    /**
     * Get replyCommentId.
     *
     * @return int
     */
    public function getReplyCommentId()
    {
        return $this->reply_comment_id;
    }

    /**
     * Set replyUserId.
     *
     * @param int $replyUserId
     *
     * @return Comment
     */
    public function setReplyUserId($replyUserId)
    {
        $this->reply_user_id = $replyUserId;

        return $this;
    }

    /**
     * Get replyUserId.
     *
     * @return int
     */
    public function getReplyUserId()
    {
        return $this->reply_user_id;
    }

    /**
     * Set content.
     *
     * @param string|null $content
     *
     * @return Comment
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set likes.
     *
     * @param string $likes
     *
     * @return Comment
     */
    public function setLikes($likes)
    {
        $this->likes = $likes;

        return $this;
    }

    /**
     * Get likes.
     *
     * @return string
     */
    public function getLikes()
    {
        return $this->likes;
    }

    /**
     * Set ip.
     *
     * @param string|null $ip
     *
     * @return Comment
     */
    public function setIp($ip = null)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string|null
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set province.
     *
     * @param string|null $province
     *
     * @return Comment
     */
    public function setProvince($province = null)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * Get province.
     *
     * @return string|null
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set city.
     *
     * @param string|null $city
     *
     * @return Comment
     */
    public function setCity($city = null)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set district.
     *
     * @param string|null $district
     *
     * @return Comment
     */
    public function setDistrict($district = null)
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get district.
     *
     * @return string|null
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * Set pOrder.
     *
     * @param int|null $pOrder
     *
     * @return Comment
     */
    public function setPOrder($pOrder = null)
    {
        $this->p_order = $pOrder;

        return $this;
    }

    /**
     * Get pOrder.
     *
     * @return int|null
     */
    public function getPOrder()
    {
        return $this->p_order;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return Comment
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set enable.
     *
     * @param int $enable
     *
     * @return Comment
     */
    public function setEnable($enable)
    {
        $this->enable = $enable;

        return $this;
    }

    /**
     * Get enable.
     *
     * @return int
     */
    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return Comment
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
     * @return Comment
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
     * @return Comment
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
     * Set aiVerifyTime.
     *
     * @param int|null $aiVerifyTime
     *
     * @return Comment
     */
    public function setAiVerifyTime($aiVerifyTime = null)
    {
        $this->ai_verify_time = $aiVerifyTime;

        return $this;
    }

    /**
     * Get aiVerifyTime.
     *
     * @return int|null
     */
    public function getAiVerifyTime()
    {
        return $this->ai_verify_time;
    }

    /**
     * Set manualVerifyTime.
     *
     * @param int|null $manualVerifyTime
     *
     * @return Comment
     */
    public function setManualVerifyTime($manualVerifyTime = null)
    {
        $this->manual_verify_time = $manualVerifyTime;

        return $this;
    }

    /**
     * Get manualVerifyTime.
     *
     * @return int|null
     */
    public function getManualVerifyTime()
    {
        return $this->manual_verify_time;
    }

    /**
     * Set aiRefuseReason.
     *
     * @param string|null $aiRefuseReason
     *
     * @return Comment
     */
    public function setAiRefuseReason($aiRefuseReason = null)
    {
        $this->ai_refuse_reason = $aiRefuseReason;

        return $this;
    }

    /**
     * Get aiRefuseReason.
     *
     * @return string|null
     */
    public function getAiRefuseReason()
    {
        return $this->ai_refuse_reason;
    }

    /**
     * Set manualRefuseReason.
     *
     * @param string|null $manualRefuseReason
     *
     * @return Comment
     */
    public function setManualRefuseReason($manualRefuseReason = null)
    {
        $this->manual_refuse_reason = $manualRefuseReason;

        return $this;
    }

    /**
     * Get manualRefuseReason.
     *
     * @return string|null
     */
    public function getManualRefuseReason()
    {
        return $this->manual_refuse_reason;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Comment
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
