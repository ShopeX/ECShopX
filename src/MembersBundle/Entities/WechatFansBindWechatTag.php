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
