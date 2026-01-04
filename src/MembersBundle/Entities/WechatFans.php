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
 * WechatUsers 微信粉丝表
 *
 * @ORM\Table(name="members_wechat_fans", options={"comment"="微信粉丝表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_openid", columns={"open_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\WechatFansRepository")
 */
class WechatFans
{
    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_appid", length=64, type="string", options={"comment"="公众号appid"})
     */
    private $authorizer_appid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="subscribed", type="boolean", nullable=true, options={"comment"="是否订阅"})
     */
    private $subscribed;

    /**
     * @var string
     *
     * @ORM\Column(name="open_id", type="string", length=40, options={"comment"="open_id"})
     */
    private $open_id;

    /**
     * @var string
     *
     * @ORM\Column(name="nickname", type="string", options={"comment"="昵称"})
     */
    private $nickname;

    /**
     * @var integer
     *
     * @ORM\Column(name="sex", type="smallint", nullable=true, options={"comment"="性别。0 未知；1 男；2 女"})
     */
    private $sex;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", nullable=true, options={"comment"="市"})
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", nullable=true, options={"comment"="国"})
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="province", type="string", nullable=true, options={"comment"="省"})
     */
    private $province;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", nullable=true, options={"comment"="语言"})
     */
    private $language;

    /**
     * @var string
     *
     * @ORM\Column(name="headimgurl", type="string", nullable=true)
     */
    private $headimgurl;

    /**
     * @var integer
     *
     * @ORM\Column(name="subscribe_time", type="integer", nullable=true, options={"comment"="订阅时间"})
     */
    private $subscribe_time;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="unionid", type="string", length=40, options={"comment"="第三方unionid"})
     */
    private $unionid;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", nullable=true, options={"comment"="备注"})
     */
    private $remark;

    /**
     * @var integer
     *
     * @ORM\Column(name="groupid", type="integer", nullable=true)
     */
    private $groupid;

    /**
     * @var string
     *
     * @ORM\Column(name="tagids", type="string", nullable=true)
     */
    private $tagids;

    /**
     * @var boolean
     *
     * @ORM\Column(name="tagpop", type="boolean", options={"comment":"列表标签弹出框所需字段", "default":false})
     */
    private $tagpop = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="remarkpop", type="boolean", options={"comment":"列表页备注弹出框所需字段", "default":false})
     */
    private $remarkpop = false;

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
     * Set subscribed
     *
     * @param boolean $subscribed
     *
     * @return WechatUsers
     */
    public function setSubscribed($subscribed)
    {
        $this->subscribed = $subscribed;

        return $this;
    }

    /**
     * Get subscribed
     *
     * @return boolean
     */
    public function getSubscribed()
    {
        return $this->subscribed;
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
     * Set nickname
     *
     * @param string $nickname
     *
     * @return WechatUsers
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Get nickname
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Set sex
     *
     * @param integer $sex
     *
     * @return WechatUsers
     */
    public function setSex($sex)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * Get sex
     *
     * @return integer
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return WechatUsers
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return WechatUsers
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set province
     *
     * @param string $province
     *
     * @return WechatUsers
     */
    public function setProvince($province)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * Get province
     *
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set language
     *
     * @param string $language
     *
     * @return WechatUsers
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set headimgurl
     *
     * @param string $headimgurl
     *
     * @return WechatUsers
     */
    public function setHeadimgurl($headimgurl)
    {
        $this->headimgurl = $headimgurl;

        return $this;
    }

    /**
     * Get headimgurl
     *
     * @return string
     */
    public function getHeadimgurl()
    {
        return $this->headimgurl;
    }

    /**
     * Set subscribeTime
     *
     * @param integer $subscribeTime
     *
     * @return WechatUsers
     */
    public function setSubscribeTime($subscribeTime)
    {
        $this->subscribe_time = $subscribeTime;

        return $this;
    }

    /**
     * Get subscribeTime
     *
     * @return integer
     */
    public function getSubscribeTime()
    {
        return $this->subscribe_time;
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
     * Set remark
     *
     * @param string $remark
     *
     * @return WechatUsers
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set groupid
     *
     * @param integer $groupid
     *
     * @return WechatUsers
     */
    public function setGroupid($groupid)
    {
        $this->groupid = $groupid;

        return $this;
    }

    /**
     * Get groupid
     *
     * @return integer
     */
    public function getGroupid()
    {
        return $this->groupid;
    }

    /**
     * Set tagids
     *
     * @param string $tagids
     *
     * @return WechatUsers
     */
    public function setTagids($tagids)
    {
        $this->tagids = $tagids;

        return $this;
    }

    /**
     * Get tagids
     *
     * @return string
     */
    public function getTagids()
    {
        return $this->tagids;
    }

    /**
     * Set tagpop
     *
     * @param boolean $tagpop
     *
     * @return WechatUsers
     */
    public function setTagpop($tagpop)
    {
        $this->tagpop = $tagpop;

        return $this;
    }

    /**
     * Get tagpop
     *
     * @return boolean
     */
    public function getTagpop()
    {
        return $this->tagpop;
    }

    /**
     * Set remarkpop
     *
     * @param boolean $remarkpop
     *
     * @return WechatUsers
     */
    public function setRemarkpop($remarkpop)
    {
        $this->remarkpop = $remarkpop;

        return $this;
    }

    /**
     * Get remarkpop
     *
     * @return boolean
     */
    public function getRemarkpop()
    {
        return $this->remarkpop;
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
}
