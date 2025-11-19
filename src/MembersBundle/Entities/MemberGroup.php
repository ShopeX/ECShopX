<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MemberGroup 用户分组表
 *
 * @ORM\Table(name="member_group", options={"comment"="分组表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_salesperson_id",     columns={"salesperson_id"}),
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberGroupRepository")
 */
class MemberGroup
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="group_id", type="bigint", options={"comment"="分组id"})
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
     * @ORM\Column(name="group_name", type="string", options={"comment"="分组名"})
     */
    private $group_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment"="导购员"})
     */
    private $salesperson_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", options={"comment"="排序"})
     */
    private $sort;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    private $updated;

    /**
     * Get groupId
     *
     * @return integer
     */
    public function getGroupId()
    {
        // ShopEx EcShopX Service Component
        return $this->group_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberGroup
     */
    public function setCompanyId($companyId)
    {
        // fe10e2f6 module
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
     * Set groupName
     *
     * @param string $groupName
     *
     * @return MemberGroup
     */
    public function setGroupName($groupName)
    {
        $this->group_name = $groupName;

        return $this;
    }

    /**
     * Get groupName
     *
     * @return string
     */
    public function getGroupName()
    {
        // fe10e2f6 module
        return $this->group_name;
    }

    /**
     * Set salespersonId
     *
     * @param integer $salespersonId
     *
     * @return MemberGroup
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId
     *
     * @return integer
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return MemberGroup
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MemberGroup
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
     * Set updated
     *
     * @param integer $updated
     *
     * @return MemberGroup
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
