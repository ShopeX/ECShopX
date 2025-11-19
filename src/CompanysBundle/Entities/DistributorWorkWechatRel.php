<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * DistributorWorkWechatRel 店务端企业微信关联表
 *
 * @ORM\Table(name="distributor_work_wechat_rel", options={"comment":"店务端企业微信关联表"},
 *    uniqueConstraints={
 *         @ORM\UniqueConstraint(name="ix_operator_company", columns={"operator_id", "company_id"}),
 *         @ORM\UniqueConstraint(name="ix_workuser_company", columns={"work_userid", "company_id"}),
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_operator_id", columns={"operator_id"}),
 *         @ORM\Index(name="idx_work_userid", columns={"work_userid"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\DistributorWorkWechatRelRepository")
 */
class DistributorWorkWechatRel
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"企业微信用户关联表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="work_userid", type="string", nullable=true,  options={"comment":"微信id", "default": ""})
     */
    private $work_userid;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"系统账户id", "default": 0})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="bound_time", type="bigint", nullable=true, options={"comment":"绑定时间", "default": 0})
     */
    private $bound_time;

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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return DistributorWorkWechatRel
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
     * Set workUserid.
     *
     * @param string|null $workUserid
     *
     * @return DistributorWorkWechatRel
     */
    public function setWorkUserid($workUserid = null)
    {
        $this->work_userid = $workUserid;

        return $this;
    }

    /**
     * Get workUserid.
     *
     * @return string|null
     */
    public function getWorkUserid()
    {
        return $this->work_userid;
    }

    /**
     * Set operatorId.
     *
     * @param int|null $operatorId
     *
     * @return DistributorWorkWechatRel
     */
    public function setOperatorId($operatorId = null)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int|null
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set boundTime.
     *
     * @param int|null $boundTime
     *
     * @return DistributorWorkWechatRel
     */
    public function setBoundTime($boundTime = null)
    {
        $this->bound_time = $boundTime;

        return $this;
    }

    /**
     * Get boundTime.
     *
     * @return int|null
     */
    public function getBoundTime()
    {
        return $this->bound_time;
    }
}
