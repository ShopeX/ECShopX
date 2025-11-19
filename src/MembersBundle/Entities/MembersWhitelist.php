<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MembersWhitelist 会员白名单
 *
 * @ORM\Table(name="members_whitelist", options={"comment"="会员白名单"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_mobile",     columns={"mobile"}, options={"lengths": {64}}),
 * },uniqueConstraints={
 *    @ORM\UniqueConstraint(name="mobile_company", columns={"mobile", "company_id"}),
 * }),
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersWhitelistRepository")
 */
class MembersWhitelist
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="whitelist_id", type="bigint", options={"comment"="白名单id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $whitelist_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, options={"comment"="手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=500, options={"comment":"名称"})
     */
    private $name;

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
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * Get whitelistId.
     *
     * @return int
     */
    public function getWhitelistId()
    {
        return $this->whitelist_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return MembersWhitelist
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
        // IDX: 2367340174
        return $this->company_id;
    }

    /**
     * Set mobile.
     *
     * @param string $mobile
     *
     * @return MembersWhitelist
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return MembersWhitelist
     */
    public function setName($name)
    {
        $this->name = fixedencrypt($name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return fixeddecrypt($this->name);
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return MembersWhitelist
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
     * @return MembersWhitelist
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
