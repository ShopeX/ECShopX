<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ActivityEnterprises 活动参与企业表
 *
 * @ORM\Table(name="employee_purchase_activity_enterprises", options={"comment"="活动参与企业表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="idx_enterprise_id", columns={"enterprise_id"}),
 * })
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\ActivityEnterprisesRepository")
 */
class ActivityEnterprises
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业ID"})
     */
    private $enterprise_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * Set activityId.
     *
     * @param int $activityId
     *
     * @return ActivityItems
     */
    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId.
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set enterpriseId.
     *
     * @param int $enterpriseId
     *
     * @return ActivityItems
     */
    public function setEnterpriseId($enterpriseId)
    {
        $this->enterprise_id = $enterpriseId;

        return $this;
    }

    /**
     * Get enterpriseId.
     *
     * @return int
     */
    public function getEnterpriseId()
    {
        return $this->enterprise_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ActivityItems
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
}
