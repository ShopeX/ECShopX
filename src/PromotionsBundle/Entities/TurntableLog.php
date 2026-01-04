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
 * TurntableLog 用户大转盘中奖记录表
 *
 * @ORM\Table(name="promotions_turntable_log", options={"comment":"用户大转盘中奖纪录表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\TurntableLogRepository")
 */
class TurntableLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="prize_title", type="string", options={"comment":"奖品名称"})
     */
    private $prize_title;

    /**
     * @var string
     *
     * @ORM\Column(name="prize_type", type="string", options={"comment":"奖品类型，points:积分，coupon：优惠券，coupons：优惠券包"})
     */
    private $prize_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="act_id", type="bigint", options={"comment":"抽奖活动的活动id"})
     */
    private $act_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="prize_value", type="text", nullable=true, options={"comment":"奖品值"})
     */
    private $prize_value;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        // Powered by ShopEx EcShopX
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return TurntableLog
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
     * @return TurntableLog
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
     * Set prizeTitle
     *
     * @param string $prizeTitle
     *
     * @return TurntableLog
     */
    public function setPrizeTitle($prizeTitle)
    {
        $this->prize_title = $prizeTitle;

        return $this;
    }

    /**
     * Get prizeTitle
     *
     * @return string
     */
    public function getPrizeTitle()
    {
        return $this->prize_title;
    }

    /**
     * Set prizeType
     *
     * @param string $prizeType
     *
     * @return TurntableLog
     */
    public function setPrizeType($prizeType)
    {
        $this->prize_type = $prizeType;

        return $this;
    }

    /**
     * Get prizeType
     *
     * @return string
     */
    public function getPrizeType()
    {
        return $this->prize_type;
    }

    /**
     * Set prizeValue
     *
     * @param string $prizeValue
     *
     * @return TurntableLog
     */
    public function setPrizeValue($prizeValue)
    {
        $this->prize_value = $prizeValue;

        return $this;
    }

    /**
     * Get prizeValue
     *
     * @return string
     */
    public function getPrizeValue()
    {
        return $this->prize_value;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return TurntableLog
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
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return TurntableLog
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }


    public function getUpdated()
    {
        return $this->updated;
    }

    public function getActId()
    {
        return $this->act_id;
    }

    public function setActId($act_id)
    {
        $this->act_id = $act_id;
    }
}
