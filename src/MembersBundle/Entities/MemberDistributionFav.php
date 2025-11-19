<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MemberDistributionFav 会员店铺收藏
 *
 * @ORM\Table(name="members_distribution_fav", options={"comment"="会员店铺收藏"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_user_id",   columns={"user_id"})
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberDistributionFavRepository")
 */
class MemberDistributionFav
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="fav_id", type="bigint", options={"comment"="收藏id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $fav_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="会员id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment"="店铺ID"})
     */
    private $distributor_id;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    protected $updated;

    /**
     * Get favId
     *
     * @return integer
     */
    public function getFavId()
    {
        return $this->fav_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberDistributionFav
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
     * @return MemberDistributionFav
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
        // ID: 53686f704578
        return $this->user_id;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return MemberDistributionFav
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MemberDistributionFav
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
     * @return MemberDistributionFav
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
