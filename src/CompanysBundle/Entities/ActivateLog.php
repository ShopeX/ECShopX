<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActivateLog 激活表
 *
 * @ORM\Table(name="activate_log", options={"comment":"激活表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\ActivateLogRepository")
 */
class ActivateLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"激活id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="resource_id", type="bigint", options={"comment":"资源包id"})
     *
     */
    private $resource_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="eid", type="string", options={"comment":"企业id"})
     */
    private $eid;

    /**
     * @var string
     *
     * @ORM\Column(name="passport_uid", type="string")
     */
    private $passport_uid;

    /**
     * @var string
     *
     * @ORM\Column(name="active_code", type="string", nullable=true, options={"comment":"激活码"})
     */
    private $active_code;

    /**
     * @var string
     *
     * @ORM\Column(name="active_type", type="string", nullable=true, options={"comment":"激活类型"})
     */
    private $active_type;

    /**
     * @var string
     *
     * @ORM\Column(name="active_status", type="string", nullable=true, options={"comment":"激活状态"})
     */
    private $active_status;

    /**
     * @var integer
     *
     * @ORM\Column(name="activeAt", type="bigint", options={"comment":"激活时间"})
     */
    private $activeAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="expiredAt", type="bigint", options={"comment":"过期时间"})
     */
    private $expiredAt;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set resourceId
     *
     * @param integer $resourceId
     *
     * @return ActivateLog
     */
    public function setResourceId($resourceId)
    {
        $this->resource_id = $resourceId;

        return $this;
    }

    /**
     * Get resourceId
     *
     * @return integer
     */
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ActivateLog
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
     * Set eid
     *
     * @param string $eid
     *
     * @return ActivateLog
     */
    public function setEid($eid)
    {
        $this->eid = $eid;

        return $this;
    }

    /**
     * Get eid
     *
     * @return string
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * Set passportUid
     *
     * @param string $passportUid
     *
     * @return ActivateLog
     */
    public function setPassportUid($passportUid)
    {
        $this->passport_uid = $passportUid;

        return $this;
    }

    /**
     * Get passportUid
     *
     * @return string
     */
    public function getPassportUid()
    {
        return $this->passport_uid;
    }

    /**
     * Set activeCode
     *
     * @param string $activeCode
     *
     * @return ActivateLog
     */
    public function setActiveCode($activeCode)
    {
        $this->active_code = $activeCode;

        return $this;
    }

    /**
     * Get activeCode
     *
     * @return string
     */
    public function getActiveCode()
    {
        return $this->active_code;
    }

    /**
     * Set activeType
     *
     * @param string $activeType
     *
     * @return ActivateLog
     */
    public function setActiveType($activeType)
    {
        $this->active_type = $activeType;

        return $this;
    }

    /**
     * Get activeType
     *
     * @return string
     */
    public function getActiveType()
    {
        return $this->active_type;
    }

    /**
     * Set activeStatus
     *
     * @param string $activeStatus
     *
     * @return ActivateLog
     */
    public function setActiveStatus($activeStatus)
    {
        $this->active_status = $activeStatus;

        return $this;
    }

    /**
     * Get activeStatus
     *
     * @return string
     */
    public function getActiveStatus()
    {
        return $this->active_status;
    }

    /**
     * Set activeAt
     *
     * @param integer $activeAt
     *
     * @return ActivateLog
     */
    public function setActiveAt($activeAt)
    {
        $this->activeAt = $activeAt;

        return $this;
    }

    /**
     * Get activeAt
     *
     * @return integer
     */
    public function getActiveAt()
    {
        return $this->activeAt;
    }

    /**
     * Set expiredAt
     *
     * @param integer $expiredAt
     *
     * @return ActivateLog
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    /**
     * Get expiredAt
     *
     * @return integer
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }
}
