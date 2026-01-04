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

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * BargainLog 砍价日志表
 *
 * @ORM\Table(name="promotions_bargain_log", options={"comment":"砍价日志表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\BargainLogRepository")
 */
class BargainLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="bargain_log_id", type="bigint", options={"comment":"砍价记录id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $bargain_log_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_appid", type="string", length=64, nullable=true, options={"comment":"公众号的appid"})
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", nullable=true, type="string", length=64, options={"comment":"小程序的appid"})
     */
    private $wxa_appid;

    /**
     * @var integer
     *
     * @ORM\Column(name="bargain_id", type="bigint", options={"comment":"砍价ID"})
     */
    private $bargain_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="open_id", type="string", length=40, nullable=true, options={"comment":"微信用户标识"})
     */
    private $open_id;

    /**
     * @var string
     *
     * @ORM\Column(name="nickname", type="string", nullable=true, options={"comment":"用户昵称"})
     */
    private $nickname;

    /**
     * @var string
     *
     * @ORM\Column(name="headimgurl", type="string", nullable=true, options={"comment":"用户头像url"})
     */
    private $headimgurl;

    /**
     * @var integer
     *
     * @ORM\Column(name="cutdown_num", type="integer", options={"comment":"砍掉金额,单位为‘分’"})
     */
    private $cutdown_num;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated;


    /**
     * Get bargainLogId
     *
     * @return integer
     */
    public function getBargainLogId()
    {
        return $this->bargain_log_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return BargainLog
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
     * @return BargainLog
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
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return BargainLog
     */
    public function setWxaAppid($wxaAppid)
    {
        $this->wxa_appid = $wxaAppid;

        return $this;
    }

    /**
     * Get wxaAppid
     *
     * @return string
     */
    public function getWxaAppid()
    {
        return $this->wxa_appid;
    }

    /**
     * Set bargainId
     *
     * @param integer $bargainId
     *
     * @return BargainLog
     */
    public function setBargainId($bargainId)
    {
        $this->bargain_id = $bargainId;

        return $this;
    }

    /**
     * Get bargainId
     *
     * @return integer
     */
    public function getBargainId()
    {
        return $this->bargain_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return BargainLog
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
     * Set openId
     *
     * @param string $openId
     *
     * @return BargainLog
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
     * @return BargainLog
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
     * Set headimgurl
     *
     * @param string $headimgurl
     *
     * @return BargainLog
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
     * Set cutdownNum
     *
     * @param integer $cutdownNum
     *
     * @return BargainLog
     */
    public function setCutdownNum($cutdownNum)
    {
        $this->cutdown_num = $cutdownNum;

        return $this;
    }

    /**
     * Get cutdownNum
     *
     * @return integer
     */
    public function getCutdownNum()
    {
        return $this->cutdown_num;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return BargainLog
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
     * @return BargainLog
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
