<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * VipGradeRelUser(付费会员的等级卡表)
 *
 * @ORM\Table(name="kaquan_vip_rel_user", options={"comment":"付费会员的等级卡表"} ,indexes={
 *    @ORM\Index(name="inx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="inx_user_id", columns={"user_id"}),
 *    @ORM\Index(name="inx_vip_grade_id", columns={"vip_grade_id"}),
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\VipGradeRelUserRepository")
 */
class VipGradeRelUser
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="integer", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="vip_type", type="string", nullable=true, options={"comment":"会员类型"})
     */
    private $vip_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="vip_grade_id", type="bigint", nullable=true, options={"comment":"会员等级id"})
     */
    private $vip_grade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="end_date", type="string", nullable=true, options={"comment":"会员到期时间"})
     */
    private $end_date;

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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return VipGradeRelUser
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
     * @return VipGradeRelUser
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
     * Set vipType
     *
     * @param string $vipType
     *
     * @return VipGradeRelUser
     */
    public function setVipType($vipType)
    {
        $this->vip_type = $vipType;

        return $this;
    }

    /**
     * Get vipType
     *
     * @return string
     */
    public function getVipType()
    {
        return $this->vip_type;
    }

    /**
     * Set vipGradeId
     *
     * @param integer $vipGradeId
     *
     * @return VipGradeRelUser
     */
    public function setVipGradeId($vipGradeId)
    {
        $this->vip_grade_id = $vipGradeId;

        return $this;
    }

    /**
     * Get vipGradeId
     *
     * @return integer
     */
    public function getVipGradeId()
    {
        return $this->vip_grade_id;
    }

    /**
     * Set endDate
     *
     * @param string $endDate
     *
     * @return VipGradeRelUser
     */
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return string
     */
    public function getEndDate()
    {
        return $this->end_date;
    }
}
