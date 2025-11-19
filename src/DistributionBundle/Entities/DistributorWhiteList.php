<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Distributor
 *
 * @ORM\Table(name="distribution_distributor_white_list",options={"comment":"店铺表"},
 *     indexes={
 *         @ORM\Index(name="ix_distributor_id", columns={"distributor_id"}),
 *         @ORM\Index(name="ix_mobile", columns={"mobile"}),
 *     },)
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributorWhiteListRepository")
 */
class DistributorWhiteList
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id", "default": true})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=50, options={"comment":"店铺手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * 店铺地址
     *
     * @ORM\Column(name="username", nullable=true, type="string", options={"comment":"名称"})
     */
    private $username;

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

    public function getId(): int
    {
        return $this->id;
    }

    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    public function setDistributorId(int $distributor_id)
    {
        $this->distributor_id = $distributor_id;
    }

    public function getCompanyId()
    {
        return $this->company_id;
    }

    public function setCompanyId(int $company_id)
    {
        $this->company_id = $company_id;
    }

    public function getMobile()
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile)
    {
        $this->mobile = $mobile;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }


}
