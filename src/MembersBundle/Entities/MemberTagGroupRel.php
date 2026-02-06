<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(
 *     name="members_tag_group_rel",
 *     options={"comment"="会员标签组与标签关系表"},
 *     indexes={
 *         @ORM\Index(name="idx_group_id", columns={"group_id"}),
 *         @ORM\Index(name="idx_tag_id", columns={"tag_id"}),
 *         @ORM\Index(name="idx_company_distributor", columns={"company_id", "distributor_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberTagGroupRelRepository")
 */
class MemberTagGroupRel
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="主键ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="bigint", options={"comment"="标签组ID"})
     */
    private $group_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="tag_id", type="bigint", options={"comment"="标签ID"})
     */
    private $tag_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

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
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return MemberTagGroupRel
     */
    public function setGroupId($groupId)
    {
        $this->group_id = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * Set tagId.
     *
     * @param int $tagId
     *
     * @return MemberTagGroupRel
     */
    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId.
     *
     * @return int
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return MemberTagGroupRel
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return MemberTagGroupRel
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
     * Set created.
     *
     * @param int $created
     *
     * @return MemberTagGroupRel
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
}
