<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SelfserviceBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * RegistrationActivityRelShop 报名活动关联店铺
 *
 * @ORM\Table(name="selfservice_registration_activity_rel_shop", options={"comment"="报名活动关联店铺"}, indexes={
 *    @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 * }),
  * @ORM\Entity(repositoryClass="SelfserviceBundle\Repositories\RegistrationActivityRelShopRepository")
 */
class RegistrationActivityRelShop
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
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"报名活动ID"})
     */
    private $activity_id;


    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺ID"})
     */
    private $distributor_id;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return RegistrationActivityRelShop
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function setActivityId($activity_id)
    {
        $this->activity_id = $activity_id;
        return $this;
    }
    
    public function getActivityId()
    {
        // 53686f704578
        return $this->activity_id;
    }
    
    public function setDistributorId($distributor_id)
    {
        $this->distributor_id = $distributor_id;
        return $this;
    }
    
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return RegistrationActivityRelShop
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
     * @return RegistrationActivityRelShop
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
