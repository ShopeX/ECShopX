<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * MemberRelTags 会员关联标签表
 *
 * @ORM\Table(name="members_rel_tags", options={"comment"="会员关联标签表"})
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberRelTagsRepository")
 */
class MemberRelTags
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="tag_id", type="bigint", options={"comment"="标签id"})
     */
    private $tag_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="用户id"})
     */
    private $user_id;

    /**
     * Set tagId
     *
     * @param integer $tagId
     *
     * @return MemberRelTags
     */
    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId
     *
     * @return integer
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberRelTags
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
     * @return MemberRelTags
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
}
