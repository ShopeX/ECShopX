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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * TradeRateReply 订单评价回复/评论表
 *
 * @ORM\Table(name="trade_rate_reply", options={"comment":"订单评价回复/评论表"},
 *     indexes={
 *         @ORM\Index(name="idx_rate_id", columns={"rate_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\TradeRateReplyRepository")
 */
class TradeRateReply
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="reply_id", type="bigint", options={"comment":"回复id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $reply_id;

    /**
     * @var integer
     *
     *
     * @ORM\Column(name="rate_id", type="bigint", options={"comment":"评价id"})
     *
     */
    private $rate_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true,  options={"comment"="用户id"})
     *
     */
    private $user_id;

    /**
     * @var integer
     *
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true,  options={"comment"="操作员的id"})
     *
     */
    private $operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string",  nullable=true, options={"comment":"评价内容"})
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="content_len", type="integer", nullable=true, options={"comment":"评价内容长度","default":0})
     */
    private $content_len = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=25, nullable=true, options={"comment":"回复角色.seller：卖家；buyer：买家"})
     */
    private $role;

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
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    protected $updated;

    /**
     * @var string
     *
     * @ORM\Column(name="unionid", type="string", nullable=true, length=60, options={"comment"="微信unionid"})
     */
    private $unionid;

    /**
     * Get replyId
     *
     * @return integer
     */
    public function getReplyId()
    {
        return $this->reply_id;
    }

    /**
     * Set rateId
     *
     * @param integer $rateId
     *
     * @return TradeRateReply
     */
    public function setRateId($rateId)
    {
        $this->rate_id = $rateId;

        return $this;
    }

    /**
     * Get rateId
     *
     * @return integer
     */
    public function getRateId()
    {
        return $this->rate_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return TradeRateReply
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
     * @return TradeRateReply
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
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return TradeRateReply
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return integer
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return TradeRateReply
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
     * Set contentLen
     *
     * @param integer $contentLen
     *
     * @return TradeRateReply
     */
    public function setContentLen($contentLen)
    {
        $this->content_len = $contentLen;

        return $this;
    }

    /**
     * Get contentLen
     *
     * @return integer
     */
    public function getContentLen()
    {
        return $this->content_len;
    }

    /**
     * Set role
     *
     * @param string $role
     *
     * @return TradeRateReply
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return TradeRateReply
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
     * Set unionid
     *
     * @param string $unionid
     *
     * @return TradeRateReply
     */
    public function setUnionid($unionid)
    {
        $this->unionid = $unionid;

        return $this;
    }

    /**
     * Get unionid
     *
     * @return string
     */
    public function getUnionid()
    {
        return $this->unionid;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return TradeRateReply
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
