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
 * WechatUsers 微信会员表
 *
 * @ORM\Table(name="members_wechatusers", options={"comment"="微信会员表"}, indexes={
 *    @ORM\Index(name="idx_unionid", columns={"unionid"}),
 *    @ORM\Index(name="idx_openid", columns={"open_id"}),
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\WechatUsersRepository")
 */
class WechatUsers
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="authorizer_appid", length=64, type="string", options={"comment":"小程序或者公众号appid"}))
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="open_id", type="string", length=40, options={"comment":"open_id"}))
     */
    private $open_id;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="unionid", type="string", length=40, options={"comment":"union_id"}))
     */
    private $unionid;

    /**
     * @var string
     *
     * @ORM\Column(name="nickname", type="string", length=500, nullable=true, options={"comment"="昵称"})
     */
    private $nickname;

    /**
     * @var string
     *
     * @ORM\Column(name="headimgurl", type="string", nullable=true, options={"comment"="头像url"})
     */
    private $headimgurl;

    /**
    * @var int
    *
    * @ORM\Column(name="inviter_id", type="integer", nullable=true, options={"comment":"来源id", "default":0})
    */
    private $inviter_id = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="source_from", type="string", nullable=true, options={"comment":"来源类型 default默认", "default":"default"})
     */
    private $source_from = 'default';

    /**
     * @var bool
     *
     * @ORM\Column(name="need_transfer", type="boolean", options={"comment":"是否需要迁移。0:不用迁移或迁移完成；1:需要迁移", "default": 0})
     */
    private $need_transfer = 0;

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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WechatUsers
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
     * Set authorizerAppid
     *
     * @param string $authorizerAppid
     *
     * @return WechatUsers
     */
    public function setAuthorizerAppid($authorizerAppid)
    {
        $this->authorizer_appid = $authorizerAppid;

        return $this;
    }

    /**
     * Get authorizerAppid
     *
     * @return string
     */
    public function getAuthorizerAppid()
    {
        return $this->authorizer_appid;
    }

    /**
     * Set openId
     *
     * @param string $openId
     *
     * @return WechatUsers
     */
    public function setOpenId($openId)
    {
        $this->open_id = $openId;

        return $this;
    }

    /**
     * Get openId
     *
     * @return string
     */
    public function getOpenId()
    {
        return $this->open_id;
    }

    /**
     * Set unionid
     *
     * @param string $unionid
     *
     * @return WechatUsers
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
     * Set created
     *
     * @param integer $created
     *
     * @return WechatUsers
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
     * @return WechatUsers
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
     * Set inviterId
     *
     * @param integer $inviterId
     *
     * @return WechatUsers
     */
    public function setInviterId($inviterId)
    {
        $this->inviter_id = $inviterId;

        return $this;
    }

    /**
     * Get inviterId
     *
     * @return integer
     */
    public function getInviterId()
    {
        return $this->inviter_id;
    }

    /**
     * Set sourceFrom
     *
     * @param string $sourceFrom
     *
     * @return WechatUsers
     */
    public function setSourceFrom($sourceFrom)
    {
        $this->source_from = $sourceFrom;

        return $this;
    }

    /**
     * Get sourceFrom
     *
     * @return string
     */
    public function getSourceFrom()
    {
        return $this->source_from;
    }

    /**
     * Set needTransfer.
     *
     * @param bool $needTransfer
     *
     * @return WechatUsers
     */
    public function setNeedTransfer($needTransfer)
    {
        $this->need_transfer = $needTransfer;

        return $this;
    }

    /**
     * Get needTransfer.
     *
     * @return bool
     */
    public function getNeedTransfer()
    {
        return $this->need_transfer;
    }

    /**
     * Set nickname.
     *
     * @param string|null $nickname
     *
     * @return WechatUsers
     */
    public function setNickname($nickname = null)
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Get nickname.
     *
     * @return string|null
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Set headimgurl.
     *
     * @param string|null $headimgurl
     *
     * @return WechatUsers
     */
    public function setHeadimgurl($headimgurl = null)
    {
        $this->headimgurl = $headimgurl;

        return $this;
    }

    /**
     * Get headimgurl.
     *
     * @return string|null
     */
    public function getHeadimgurl()
    {
        return $this->headimgurl;
    }
}
