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
 * CouponGiveLog 优惠券赠送表
 *
 * @ORM\Table(name="coupon_give_log", options={"comment":"优惠券赠送表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\CouponGiveLogRepository")
 */
class CouponGiveLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="give_log_id", type="bigint", options={"comment":"优惠券赠送失败记录id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $give_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"商户id"})
     */
    private $company_id;
    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sender", type="string", options={"comment":"发送者"})
     */
    private $sender;

    /**
     * @var integer
     *
     * @ORM\Column(name="number", type="bigint", options={"comment":"赠送数量"})
     */
    private $number;

    /**
     * @var integer
     *
     * @ORM\Column(name="error", type="bigint", options={"comment":"失败数量"})
     */
    private $error;

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
    private $updated;

    /**
     * Get giveId
     *
     * @return integer
     */
    public function getGiveId()
    {
        return $this->give_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return CouponGiveLog
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
     * Set sender
     *
     * @param string $sender
     *
     * @return CouponGiveLog
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender
     *
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set number
     *
     * @param integer $number
     *
     * @return CouponGiveLog
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set error
     *
     * @param integer $error
     *
     * @return CouponGiveLog
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return integer
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return CouponGiveLog
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return CouponGiveLog
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return CouponGiveLog
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
