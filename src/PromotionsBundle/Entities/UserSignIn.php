<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UserSignIn 用户签到表
 *
 * @ORM\Table(name="user_signin", options={"comment":"用户签到表"},indexes={
 *     @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *     @ORM\Index(name="ix_user_id", columns={"user_id"}),
 *     @ORM\Index(name="ix_sign_date", columns={"sign_date"})
 *  })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\UserSigninRepository")
 */
class UserSignIn
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"记录id"})
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
     * @var date
     *
     * @ORM\Column(name="sign_date", type="date", options={"comment":"签到日期"})
     */
    private $sign_date;

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

    public function getId(): int
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->id;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function setCompanyId(int $company_id): void
    {
        $this->company_id = $company_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getSignDate()
    {
        return $this->sign_date;
    }

    public function setSignDate($sign_date): void
    {
        $this->sign_date = $sign_date;
    }

}
