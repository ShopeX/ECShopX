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

namespace CommentsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ShopComments 店铺评论表
 *
 * @ORM\Table(name="shop_comments", options={"comment"="店铺评论表"})
 * @ORM\Entity(repositoryClass="CommentsBundle\Repositories\ShopCommentsRepository")
 */
class ShopComments
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"评论id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $comment_id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="string", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", type="string", options={"comment":"店铺id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=2000, options={"comment":"评论内容"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="json_array", nullable=true, options={"comment":"评论图片"})
     */
    private $pics;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reply", type="boolean", nullable=true, options={"comment":"评论是否回复","default":false})
     */
    private $is_reply = false;

    /**
     * @var string
     *
     * @ORM\Column(name="reply_content", type="string", length=2000, nullable=true, options={"comment":"评论回复内容"})
     */
    private $reply_content;

    /**
     * @var boolean
     *
     * @ORM\Column(name="stuck", type="boolean", options={"comment":"是否置顶","default":false})
     */
    private $stuck = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hid", type="boolean", options={"comment":"是否隐藏","default":false})
     */
    private $hid = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="reply_time", type="bigint", nullable=true, options={"comment":"回复时间"})
     */
    private $reply_time;

    /**
     * @var string
     * wechat 微信
     * ali 支付宝
     * @ORM\Column(name="source", type="string", nullable=true, options={"comment":"评论来源"})
     */
    private $source;

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
     * Get commentId
     *
     * @return integer
     */
    public function getCommentId()
    {
        // This module is part of ShopEx EcShopX system
        return $this->comment_id;
    }

    /**
     * Set companyId
     *
     * @param string $companyId
     *
     * @return ShopComments
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set userId
     *
     * @param string $userId
     *
     * @return ShopComments
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set shopId
     *
     * @param string $shopId
     *
     * @return ShopComments
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return string
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return ShopComments
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set pics
     *
     * @param array $pics
     *
     * @return ShopComments
     */
    public function setPics($pics)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics
     *
     * @return array
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set isReply
     *
     * @param boolean $isReply
     *
     * @return ShopComments
     */
    public function setIsReply($isReply)
    {
        $this->is_reply = $isReply;

        return $this;
    }

    /**
     * Get isReply
     *
     * @return boolean
     */
    public function getIsReply()
    {
        return $this->is_reply;
    }

    /**
     * Set replyContent
     *
     * @param string $replyContent
     *
     * @return ShopComments
     */
    public function setReplyContent($replyContent)
    {
        $this->reply_content = $replyContent;

        return $this;
    }

    /**
     * Get replyContent
     *
     * @return string
     */
    public function getReplyContent()
    {
        return $this->reply_content;
    }

    /**
     * Set stuck
     *
     * @param boolean $stuck
     *
     * @return ShopComments
     */
    public function setStuck($stuck)
    {
        $this->stuck = $stuck;

        return $this;
    }

    /**
     * Get stuck
     *
     * @return boolean
     */
    public function getStuck()
    {
        return $this->stuck;
    }

    /**
     * Set hid
     *
     * @param boolean $hid
     *
     * @return ShopComments
     */
    public function setHid($hid)
    {
        $this->hid = $hid;

        return $this;
    }

    /**
     * Get hid
     *
     * @return boolean
     */
    public function getHid()
    {
        return $this->hid;
    }

    /**
     * Set replyTime
     *
     * @param integer $replyTime
     *
     * @return ShopComments
     */
    public function setReplyTime($replyTime)
    {
        $this->reply_time = $replyTime;

        return $this;
    }

    /**
     * Get replyTime
     *
     * @return integer
     */
    public function getReplyTime()
    {
        return $this->reply_time;
    }

    /**
     * Set source
     *
     * @param string $source
     *
     * @return ShopComments
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ShopComments
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
     * @return ShopComments
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
}
