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

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDiscountLogs 优惠券核销记录表
 *
 * @ORM\Table(name="kaquan_user_discount_logs", options={"comment":"优惠券核销记录表"})
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\UserDiscountLogsRepository")
 */
class UserDiscountLogs
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", length=64, options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var bigint
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户的唯一标识"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=500, nullable=true, options={"comment":"姓名"})
     */
    private $username;

    /**
     * @var integer
     *
     * @ORM\Column(name="card_id", type="bigint", length=40, options={"comment":"微信用户领取的卡券 id "})
     */
    private $card_id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=30, options={"comment":"卡券 code 序列号"})
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=27, options={"comment":"卡券名,最大9个汉字"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="card_type", type="string", options={"comment":"优惠券类型，可选值有 discount 折扣券;cash:代金券;gift:兑换券"})
     */
    private $card_type;

    /**
    * @var string
    *
    * @ORM\Column(name="shop_name", type="string", nullable=true, options={"comment":"核销卡券的门店名称"})
    */
    private $shop_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="used_time", type="integer", options={"comment":"核销时间"})
     */
    private $used_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="used_status", type="string", nullable=true, options={"comment":"核销状态；consume:核销，callback:回退"})
     */
    private $used_status = 'consume';

    /**
     * @var integer
     *
     * @ORM\Column(name="used_order", type="string", nullable=true, options={"comment":"核销订单"})
     */
    private $used_order;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return UserDiscountLogs
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UserDiscountLogs
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
     * Set mobile
     *
     * @param string $mobile
     *
     * @return UserDiscountLogs
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return UserDiscountLogs
     */
    public function setUsername($username)
    {
        $this->username = fixedencrypt($username);

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return fixeddecrypt($this->username);
    }

    /**
     * Set cardId
     *
     * @param integer $cardId
     *
     * @return UserDiscountLogs
     */
    public function setCardId($cardId)
    {
        $this->card_id = $cardId;

        return $this;
    }

    /**
     * Get cardId
     *
     * @return integer
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return UserDiscountLogs
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return UserDiscountLogs
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set cardType
     *
     * @param string $cardType
     *
     * @return UserDiscountLogs
     */
    public function setCardType($cardType)
    {
        $this->card_type = $cardType;

        return $this;
    }

    /**
     * Get cardType
     *
     * @return string
     */
    public function getCardType()
    {
        return $this->card_type;
    }

    /**
     * Set shopName
     *
     * @param string $shopName
     *
     * @return UserDiscountLogs
     */
    public function setShopName($shopName)
    {
        $this->shop_name = $shopName;

        return $this;
    }

    /**
     * Get shopName
     *
     * @return string
     */
    public function getShopName()
    {
        return $this->shop_name;
    }

    /**
     * Set usedTime
     *
     * @param integer $usedTime
     *
     * @return UserDiscountLogs
     */
    public function setUsedTime($usedTime)
    {
        $this->used_time = $usedTime;

        return $this;
    }

    /**
     * Get usedTime
     *
     * @return integer
     */
    public function getUsedTime()
    {
        return $this->used_time;
    }

    /**
     * Set usedStatus
     *
     * @param string $usedStatus
     *
     * @return UserDiscountLogs
     */
    public function setUsedStatus($usedStatus)
    {
        $this->used_status = $usedStatus;

        return $this;
    }

    /**
     * Get usedStatus
     *
     * @return string
     */
    public function getUsedStatus()
    {
        return $this->used_status;
    }

    /**
     * Set usedOrder
     *
     * @param string $usedOrder
     *
     * @return UserDiscountLogs
     */
    public function setUsedOrder($usedOrder)
    {
        $this->used_order = $usedOrder;

        return $this;
    }

    /**
     * Get usedOrder
     *
     * @return string
     */
    public function getUsedOrder()
    {
        return $this->used_order;
    }
}
