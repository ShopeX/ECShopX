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
 *     name="members_tag_groups",
 *     options={"comment"="会员标签组"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberTagGroupRepository")
 */
class MemberTagGroup
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="group_id", type="bigint", options={"comment"="标签组ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $group_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="group_name", type="string", length=100, options={"comment"="标签组名称"})
     */
    private $group_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true, options={"comment"="描述"})
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="wechat_group_id", type="string", length=100, nullable=true, options={"comment"="企业微信标签组ID"})
     */
    private $wechat_group_id;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return MemberTagGroup
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
     * Set groupName.
     *
     * @param string $groupName
     *
     * @return MemberTagGroup
     */
    public function setGroupName($groupName)
    {
        $this->group_name = $groupName;

        return $this;
    }

    /**
     * Get groupName.
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->group_name;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return MemberTagGroup
     */
    public function setDescription($description = null)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return MemberTagGroup
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
     * Set wechatGroupId.
     *
     * @param string|null $wechatGroupId
     *
     * @return MemberTagGroup
     */
    public function setWechatGroupId($wechatGroupId = null)
    {
        $this->wechat_group_id = $wechatGroupId;

        return $this;
    }

    /**
     * Get wechatGroupId.
     *
     * @return string|null
     */
    public function getWechatGroupId()
    {
        return $this->wechat_group_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return MemberTagGroup
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
     * @return MemberTagGroup
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
