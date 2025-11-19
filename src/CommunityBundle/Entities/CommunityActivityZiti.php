<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CommunityBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * community_activity_ziti 社区拼团活动自提表
 *
 * @ORM\Table(name="community_activity_ziti", options={"comment"="社区拼团活动自提表"}, indexes={
 *    @ORM\Index(name="ix_activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="ix_ziti_id", columns={"ziti_id"})
 * })
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityActivityZitiRepository")
 */
class CommunityActivityZiti
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="ziti_id", type="bigint", options={"comment":"自提ID"})
     */
    private $ziti_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="condition_num", type="integer", options={"comment":"成团数量"})
     */
    private $condition_num;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", nullable=true, options={"comment":"备注"}))
     */
    private $remark;

    /**
     * get Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * get ActivityId
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * set ActivityId
     *
     * @param int $activity_id
     *
     * @return self
     */
    public function setActivityId($activity_id)
    {
        $this->activity_id = $activity_id;
        return $this;
    }

    /**
     * get ZitiId
     *
     * @return int
     */
    public function getZitiId()
    {
        return $this->ziti_id;
    }

    /**
     * set ZitiId
     *
     * @param int $ziti_id
     *
     * @return self
     */
    public function setZitiId($ziti_id)
    {
        // ShopEx framework
        $this->ziti_id = $ziti_id;
        return $this;
    }

    /**
     * get ConditionNum
     *
     * @return int
     */
    public function getConditionNum()
    {
        return $this->condition_num;
    }

    /**
     * set ConditionNum
     *
     * @param int $condition_num
     *
     * @return self
     */
    public function setConditionNum($condition_num)
    {
        $this->condition_num = $condition_num;
        return $this;
    }

    /**
     * get Remark
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * set Remark
     *
     * @param string $remark
     *
     * @return self
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;
        return $this;
    }


}
