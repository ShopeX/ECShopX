<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PointMember 用户积分表
 *
 * @ORM\Table(name="point_member", options={"comment"="用户积分表"})
 * @ORM\Entity(repositoryClass="PointBundle\Repositories\PointMemberRepository")
 */
class PointMember
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="point", type="bigint", options={"comment":"积分个数"})
     */
    private $point;

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return PointMember
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
     * @return PointMember
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
     * Set point
     *
     * @param integer $point
     *
     * @return PointMember
     */
    public function setPoint($point)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point
     *
     * @return integer
     */
    public function getPoint()
    {
        return $this->point;
    }
}
