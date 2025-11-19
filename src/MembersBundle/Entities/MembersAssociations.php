<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * members_associations 不同平台会员关联表，关联到members表
 *
 * @ORM\Table(name="members_associations", options={"comment":"不同平台会员关联表，关联到members表"},
 *     indexes={
 *         @ORM\Index(name="ind_member_id_miss", columns={"unionid","company_id","user_type"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersAssociationsRepository")
 */
class MembersAssociations
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
     * @ORM\Column(name="unionid", type="string", length=40, options={"comment":"第三方unionid"})
     */
    private $unionid;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="user_type", type="string", length=30, options={"comment":"用户类型，可选值有 wechat:微信;ali:支付宝"})
     */
    private $user_type;

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return UsersAssociations
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
     * Set unionid
     *
     * @param string $unionid
     *
     * @return UsersAssociations
     */
    public function setUnionid($unionid)
    {
        $this->unionid = $unionid;

        return $this;
    }

    /**
     * Get unionid
     *
     * @return string
     */
    public function getUnionid()
    {
        return $this->unionid;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UsersAssociations
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
     * Set userType
     *
     * @param string $userType
     *
     * @return UsersAssociations
     */
    public function setUserType($userType)
    {
        $this->user_type = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return string
     */
    public function getUserType()
    {
        return $this->user_type;
    }
}
