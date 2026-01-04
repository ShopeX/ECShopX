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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * RightsOperateLogs 权益操作日志表
 *
 * @ORM\Table(name="orders_rights_operate_logs", options={"comment":"权益操作日志表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\RightsOperateLogsRepository")
 */
class RightsOperateLogs
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"权益延期操作日志"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rights_id", type="bigint", options={"comment":"权益ID"})
     */
    private $rights_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=255, options={"comment":"操作备注"})
     */
    private $remark;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"操作员Id"})
     */
    private $operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="operator", type="string", nullable=true, options={"comment":"操作员"})
     */
    private $operator;

    /**
     * @var integer
     *
     * @ORM\Column(name="original_date", type="integer", nullable=true, options={"comment":"延期之前日期"})
     */
    private $original_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="delay_date", type="integer", nullable=true, options={"comment":"延期之后日期"})
     */
    private $delay_date;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set rightsId
     *
     * @param integer $rightsId
     *
     * @return RightsOperateLogs
     */
    public function setRightsId($rightsId)
    {
        $this->rights_id = $rightsId;

        return $this;
    }

    /**
     * Get rightsId
     *
     * @return integer
     */
    public function getRightsId()
    {
        return $this->rights_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return RightsOperateLogs
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RightsOperateLogs
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
     * Set remark
     *
     * @param string $remark
     *
     * @return RightsOperateLogs
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return RightsOperateLogs
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return integer
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set operator
     *
     * @param string $operator
     *
     * @return RightsOperateLogs
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set originalDate
     *
     * @param integer $originalDate
     *
     * @return RightsOperateLogs
     */
    public function setOriginalDate($originalDate)
    {
        $this->original_date = $originalDate;

        return $this;
    }

    /**
     * Get originalDate
     *
     * @return integer
     */
    public function getOriginalDate()
    {
        return $this->original_date;
    }

    /**
     * Set delayDate
     *
     * @param integer $delayDate
     *
     * @return RightsOperateLogs
     */
    public function setDelayDate($delayDate)
    {
        $this->delay_date = $delayDate;

        return $this;
    }

    /**
     * Get delayDate
     *
     * @return integer
     */
    public function getDelayDate()
    {
        return $this->delay_date;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return RightsOperateLogs
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
     * @return RightsOperateLogs
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
