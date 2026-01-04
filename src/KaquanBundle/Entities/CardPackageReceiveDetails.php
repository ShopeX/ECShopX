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
 * CardPackageReceiveDetails(卡券包领取记录详情表)
 *
 * @ORM\Table(name="card_package_receive_details", options={"comment":"卡券包领取记录详情表"},indexes={
 *  @ORM\Index(name="idx_companyid", columns={"company_id"}),
 *  @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *  @ORM\Index(name="idx_receive_id", columns={"receive_id"})
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\CardPackageReceiveDetailsRepository")
 */
class CardPackageReceiveDetails
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"主键ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="receive_id", type="bigint", nullable=false, options={"comment":"用户领取表主键ID"})
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
     * @var integer
     * @ORM\Column(name="card_id", type="bigint", nullable=false, options={"comment":"卡券ID"})
     */
    private $card_id;

    /**
     * @var string
     * @ORM\Column(name="message", type="string", length=100, nullable=false, options={"comment":"领取描述"})
     */
    private $message;

    /**
     * @var integer
     * @ORM\Column(name="receive_status", type="integer", nullable=false, options={"comment":"1/2/3 领取中/领取成功/领取失败"})
     */
    private $receive_status;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set receiveId.
     *
     * @param int $receiveId
     *
     * @return CardPackageReceiveDetails
     */
    public function setReceiveId($receiveId)
    {
        $this->receive_id = $receiveId;

        return $this;
    }

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
     * @return CardPackageReceiveDetails
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
     * @return CardPackageReceiveDetails
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
     * @return CardPackageReceiveDetails
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
     * Set cardId.
     *
     * @param int $cardId
     *
     * @return CardPackageReceiveDetails
     */
    public function setCardId($cardId)
    {
        $this->card_id = $cardId;

        return $this;
    }

    /**
     * Get cardId.
     *
     * @return int
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * Set message.
     *
     * @param string $message
     *
     * @return CardPackageReceiveDetails
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set receiveStatus.
     *
     * @param int $receiveStatus
     *
     * @return CardPackageReceiveDetails
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
     * Set created.
     *
     * @param int $created
     *
     * @return CardPackageReceiveDetails
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
     * @return CardPackageReceiveDetails
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
