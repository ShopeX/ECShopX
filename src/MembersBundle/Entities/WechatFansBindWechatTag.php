<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * WechatFansBindWechatTag 粉丝关联标微信签表
 *
 * @ORM\Table(name="wechatfans_bind_wechattag", options={"comment":"粉丝关联标微信签表"})
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\WechatFansBindWechatTagRepository")
 */
class WechatFansBindWechatTag
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="tag_id", type="bigint", options={"comment":"标签id"})
     */
    private $tag_id;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="open_id", type="string", length=40, options={"comment":"open_id"})
     */
    private $open_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     * @ORM\Column(name="authorizer_appid", type="string", length=64, options={"comment":"公众号appid"})
     */
    private $authorizer_appid;

    /**
     * Set tagId
     *
     * @param integer $tagId
     *
     * @return WechatFansBindWechatTag
     */
    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId
     *
     * @return integer
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set openId
     *
     * @param string $openId
     *
     * @return WechatFansBindWechatTag
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WechatFansBindWechatTag
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
     * @return WechatFansBindWechatTag
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
}
