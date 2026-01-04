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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CardPackageReceive(卡券包领取记录表)
 *
 * @ORM\Table(name="card_package_receive", options={"comment":"卡券包领取记录表"},indexes={
 *  @ORM\Index(name="idx_companyid", columns={"company_id"}),
 *  @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *  @ORM\Index(name="idx_front_show", columns={"front_show"})
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\CardPackageReceiveRepository")
 */
class CardPackageReceive
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="receive_id", type="bigint", nullable=false, options={"comment":"主键ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $receive_id;

    /**
     * @var integer
     * @ORM\Column(name="package_id", type="bigint", nullable=false, options={"comment":"卡券包ID"})
     */
    private $package_id;

    /**
     * @var integer
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     * @ORM\Column(name="user_id", type="bigint", nullable=false, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     * @ORM\Column(name="receive_type", type="string", length=20, nullable=false, options={"comment":"领取类型，vip_grade/grade/template vip等级/等级/卡券模版"})
     */
    private $receive_type;

    /**
     * @var integer
     * @ORM\Column(name="receive_status", type="integer", nullable=false, options={"comment":"1/2/3 领取中/领取成功/领取失败"})
     */
    private $receive_status;

    /**
     * @var integer
     * @ORM\Column(name="front_show", type="integer", nullable=false, options={"comment":"前端弹框是否展示过"})
     */
    private $front_show;

    /**
     * @var integer
     * @ORM\Column(name="receive_time", type="integer", nullable=false, options={"comment":"领取时间"})
     */
    private $receive_time;

    /**
     * @var integer
     * @ORM\Column(name="success_count", type="integer", nullable=false, options={"comment":"成功领取卡券数量"})
     */
    private $success_count;

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
     * @ORM\Column(type="integer")
     */
    protected $updated;


    /**
     * Get receiveId.
     *
     * @return int
     */
    public function getReceiveId()
    {
        return $this->receive_id;
    }

    /**
     * Set packageId.
     *
     * @param int $packageId
     *
     * @return CardPackageReceive
     */
    public function setPackageId($packageId)
    {
        $this->package_id = $packageId;

        return $this;
    }

    /**
     * Get packageId.
     *
     * @return int
     */
    public function getPackageId()
    {
        return $this->package_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return CardPackageReceive
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CardPackageReceive
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set receiveType.
     *
     * @param string $receiveType
     *
     * @return CardPackageReceive
     */
    public function setReceiveType($receiveType)
    {
        $this->receive_type = $receiveType;

        return $this;
    }

    /**
     * Get receiveType.
     *
     * @return string
     */
    public function getReceiveType()
    {
        return $this->receive_type;
    }

    /**
     * Set receiveStatus.
     *
     * @param int $receiveStatus
     *
     * @return CardPackageReceive
     */
    public function setReceiveStatus($receiveStatus)
    {
        $this->receive_status = $receiveStatus;

        return $this;
    }

    /**
     * Get receiveStatus.
     *
     * @return int
     */
    public function getReceiveStatus()
    {
        return $this->receive_status;
    }

    /**
     * Set frontShow.
     *
     * @param int $frontShow
     *
     * @return CardPackageReceive
     */
    public function setFrontShow(int $frontShow)
    {
        $this->front_show = $frontShow;

        return $this;
    }

    /**
     * Get frontShow.
     *
     * @return int
     */
    public function getFrontShow()
    {
        return $this->front_show;
    }

    /**
     * Set receiveTime.
     *
     * @param int $receiveTime
     *
     * @return CardPackageReceive
     */
    public function setReceiveTime($receiveTime)
    {
        $this->receive_time = $receiveTime;

        return $this;
    }

    /**
     * Get receiveTime.
     *
     * @return int
     */
    public function getReceiveTime()
    {
        return $this->receive_time;
    }

    /**
     * Set successCount.
     *
     * @param int $successCount
     *
     * @return CardPackageReceive
     */
    public function setSuccessCount($successCount)
    {
        $this->success_count = $successCount;

        return $this;
    }

    /**
     * Get successCount.
     *
     * @return int
     */
    public function getSuccessCount()
    {
        return $this->success_count;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return CardPackageReceive
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int $updated
     *
     * @return CardPackageReceive
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
