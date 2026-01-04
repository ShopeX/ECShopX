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

namespace PointBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PointMemberLog 用户积分记录表
 *
 * @ORM\Table(name="point_member_log", options={"comment"="用户积分记录表"}, indexes={
 *    @ORM\Index(name="idx_company_id_external_id", columns={"company_id", "external_id"}),
 *    @ORM\Index(name="idx_company_id_operater", columns={"company_id", "operater"}),
 * })
 * @ORM\Entity(repositoryClass="PointBundle\Repositories\PointMemberLogRepository")
 */
class PointMemberLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"积分记录id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="journal_type", type="smallint", options={"comment":"积分交易类型，1:注册送积分 2.推荐送分 3.充值返积分 4.推广注册返积分 5.积分换购 6.储值兑换积分 7.订单返积分 8.会员等级返佣 9.取消订处理积分 10.售后处理积分 11.大转盘抽奖送积分 12:管理员手动调整积分 13.外部开发者同步进来的会员积分 14:会员信息导入，初始化积分 15:会员信息导入，更新会员调整积分; 16 活动报名送积分"})
     */
    private $journal_type;

    /**
     * @var string
     *
     * @ORM\Column(name="point_desc", type="string", options={"default":"", "comment":"积分描述"})
     */
    private $point_desc;

    /**
     * @var int
     *
     * @ORM\Column(name="income", type="integer", options={"default":0, "unsigned":true, "comment":"入账积分"})
     */
    private $income = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="outcome", type="integer", options={"default":0, "unsigned":true, "comment":"出账积分"})
     */
    private $outcome = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, nullable=true, options={"comment":"订单号(充值)"})
     */
    private $order_id;

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
     * @var string
     *
     * @ORM\Column(name="external_id", type="string", length=50, nullable=false, options={"comment":"外部唯一标识，外部调用方自定义的值", "default":""})
     */
    private $external_id;

    /**
     * @var string
     *
     * @ORM\Column(name="operater", type="string", length=50, nullable=false, options={"comment":"操作员名称", "default":""})
     */
    private $operater;

    /**
     * @var string
     *
     * @ORM\Column(name="operater_remark", type="string", length=255, nullable=false, options={"comment":"操作员备注", "default":""})
     */
    private $operater_remark;

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
     * Set userId
     *
     * @param integer $userId
     *
     * @return PointMemberLog
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
        // 1e236443e5a30b09910e0d48c994b8e6 core
        return $this->user_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PointMemberLog
     */
    public function setCompanyId($companyId)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
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
     * Set journalType
     *
     * @param integer $journalType
     *
     * @return PointMemberLog
     */
    public function setJournalType($journalType)
    {
        $this->journal_type = $journalType;

        return $this;
    }

    /**
     * Get journalType
     *
     * @return integer
     */
    public function getJournalType()
    {
        return $this->journal_type;
    }

    /**
     * Set pointDesc
     *
     * @param string $pointDesc
     *
     * @return PointMemberLog
     */
    public function setPointDesc($pointDesc)
    {
        $this->point_desc = $pointDesc;

        return $this;
    }

    /**
     * Get pointDesc
     *
     * @return string
     */
    public function getPointDesc()
    {
        return $this->point_desc;
    }

    /**
     * Set income
     *
     * @param integer $income
     *
     * @return PointMemberLog
     */
    public function setIncome($income)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get income
     *
     * @return integer
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * Set outcome
     *
     * @param integer $outcome
     *
     * @return PointMemberLog
     */
    public function setOutcome($outcome)
    {
        $this->outcome = $outcome;

        return $this;
    }

    /**
     * Get outcome
     *
     * @return integer
     */
    public function getOutcome()
    {
        return $this->outcome;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return PointMemberLog
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return PointMemberLog
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
     * @return PointMemberLog
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

    /**
     * Set operater.
     *
     * @param string $operater
     *
     * @return PointMemberLog
     */
    public function setOperater($operater)
    {
        $this->operater = $operater;

        return $this;
    }

    /**
     * Get operater.
     *
     * @return string
     */
    public function getOperater()
    {
        return $this->operater;
    }

    /**
     * Set operaterRemark.
     *
     * @param string $operaterRemark
     *
     * @return PointMemberLog
     */
    public function setOperaterRemark($operaterRemark)
    {
        $this->operater_remark = $operaterRemark;

        return $this;
    }

    /**
     * Get operaterRemark.
     *
     * @return string
     */
    public function getOperaterRemark()
    {
        return $this->operater_remark;
    }

    /**
     * Set externalId.
     *
     * @param string $externalId
     *
     * @return PointMemberLog
     */
    public function setExternalId($externalId)
    {
        $this->external_id = $externalId;

        return $this;
    }

    /**
     * Get externalId.
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->external_id;
    }
}
