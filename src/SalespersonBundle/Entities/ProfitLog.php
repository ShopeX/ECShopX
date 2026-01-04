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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ProfitLog 分润记录表
 *
 * @ORM\Table(name="companys_profit_log", options={"comment":"分润记录表"})
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\ProfitLogRepository")
 */
class ProfitLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
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
     * @ORM\Column(name="order_id", type="string", length=64, nullable=true, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_user_id", type="bigint", options={"comment":"分润类型id"})
     */
    private $profit_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_user_type", type="bigint", options={"comment":"1 导购 2 店铺 3 区域经销商 4 总部"})
     */
    private $profit_user_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_type", type="bigint", options={"comment":"1 拉新分润 2 推广提成 3 货款 4 补贴"})
     */
    private $profit_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="bigint", options={"comment":"资金状态 1 取消退款 2 售后退款 3 提现扣减 11 购物冻结 12 购物可体现"})
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="income_fee", type="bigint", nullable=true, options={"comment":"增加金额"})
     */
    private $income_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="outcome_fee", type="bigint", nullable=true, options={"comment":"扣减金额"})
     */
    private $outcome_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", length=60, type="string", options={"comment":"资金状态"})
     */
    private $remark;

    /**
     * @var json_array
     *
     * @ORM\Column(name="params", type="json_array", nullable=true, options={"comment":"冗余参数 例如 order_id"})
     */
    private $params;

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
     * @ORM\Column(type="integer")
     */
    private $updated;

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
     * @return ProfitLog
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
     * Set orderId.
     *
     * @param string|null $orderId
     *
     * @return ProfitLog
     */
    public function setOrderId($orderId = null)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set profitUserId.
     *
     * @param int $profitUserId
     *
     * @return ProfitLog
     */
    public function setProfitUserId($profitUserId)
    {
        $this->profit_user_id = $profitUserId;

        return $this;
    }

    /**
     * Get profitUserId.
     *
     * @return int
     */
    public function getProfitUserId()
    {
        return $this->profit_user_id;
    }

    /**
     * Set profitUserType.
     *
     * @param int $profitUserType
     *
     * @return ProfitLog
     */
    public function setProfitUserType($profitUserType)
    {
        $this->profit_user_type = $profitUserType;

        return $this;
    }

    /**
     * Get profitUserType.
     *
     * @return int
     */
    public function getProfitUserType()
    {
        return $this->profit_user_type;
    }

    /**
     * Set profitType.
     *
     * @param int $profitType
     *
     * @return ProfitLog
     */
    public function setProfitType($profitType)
    {
        $this->profit_type = $profitType;

        return $this;
    }

    /**
     * Get profitType.
     *
     * @return int
     */
    public function getProfitType()
    {
        return $this->profit_type;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return ProfitLog
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set incomeFee.
     *
     * @param int|null $incomeFee
     *
     * @return ProfitLog
     */
    public function setIncomeFee($incomeFee = null)
    {
        $this->income_fee = $incomeFee;

        return $this;
    }

    /**
     * Get incomeFee.
     *
     * @return int|null
     */
    public function getIncomeFee()
    {
        return $this->income_fee;
    }

    /**
     * Set outcomeFee.
     *
     * @param int|null $outcomeFee
     *
     * @return ProfitLog
     */
    public function setOutcomeFee($outcomeFee = null)
    {
        $this->outcome_fee = $outcomeFee;

        return $this;
    }

    /**
     * Get outcomeFee.
     *
     * @return int|null
     */
    public function getOutcomeFee()
    {
        return $this->outcome_fee;
    }

    /**
     * Set remark.
     *
     * @param string $remark
     *
     * @return ProfitLog
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark.
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set params.
     *
     * @param array|null $params
     *
     * @return ProfitLog
     */
    public function setParams($params = null)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params.
     *
     * @return array|null
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ProfitLog
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
     * @return ProfitLog
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
