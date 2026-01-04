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
 * RightsTransferLogs 权益转赠表
 *
 * @ORM\Table(name="orders_rights_transfer_logs", options={"comment":"权益转赠表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\RightsTransferLogsRepository")
 */
class RightsTransferLogs
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
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="transfer_user_id", type="bigint", options={"comment":"转让用户id"})
     */
    private $transfer_user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, options={"comment":"用户手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="transfer_mobile", type="string", length=255, options={"comment":"转让用户手机号"})
     */
    private $transfer_mobile;

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
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
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
     * @return RightsTransferLogs
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
     * @return RightsTransferLogs
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
     * Set transferUserId
     *
     * @param integer $transferUserId
     *
     * @return RightsTransferLogs
     */
    public function setTransferUserId($transferUserId)
    {
        $this->transfer_user_id = $transferUserId;

        return $this;
    }

    /**
     * Get transferUserId
     *
     * @return integer
     */
    public function getTransferUserId()
    {
        return $this->transfer_user_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RightsTransferLogs
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
     * @return RightsTransferLogs
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
     * @return RightsTransferLogs
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
     * @return RightsTransferLogs
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
     * Set created
     *
     * @param integer $created
     *
     * @return RightsTransferLogs
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
     * Set mobile
     *
     * @param string $mobile
     *
     * @return RightsTransferLogs
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set transferMobile
     *
     * @param string $transferMobile
     *
     * @return RightsTransferLogs
     */
    public function setTransferMobile($transferMobile)
    {
        $this->transfer_mobile = fixedencrypt($transferMobile);

        return $this;
    }

    /**
     * Get transferMobile
     *
     * @return string
     */
    public function getTransferMobile()
    {
        return fixeddecrypt($this->transfer_mobile);
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return RightsTransferLogs
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
