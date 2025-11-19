<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DepositBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UserDeposit 用户储值金额
 *
 * @ORM\Table(name="user_deposit", options={"comment":"用户储值金额表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="DepositBundle\Repositories\UserDepositRepository")
 */
class UserDeposit
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="deposit_id", type="bigint", options={"comment":"用户储值ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $deposit_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
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
     * @ORM\Column(name="money", type="bigint", options={"comment":"充值金额/消费金额。 单位是分"})
     */
    private $money;

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
     * Get depositId.
     *
     * @return int
     */
    public function getDepositId()
    {
        return $this->deposit_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return UserDeposit
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
     * @return UserDeposit
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
     * Set money.
     *
     * @param int $money
     *
     * @return UserDeposit
     */
    public function setMoney($money)
    {
        $this->money = $money;

        return $this;
    }

    /**
     * Get money.
     *
     * @return int
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return UserDeposit
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
     * @param int|null $updated
     *
     * @return UserDeposit
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
