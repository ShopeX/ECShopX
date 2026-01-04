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

namespace HfPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * HfpayTradeRecord 汇付记账表
 *
 * @ORM\Table(name="hfpay_trade_record", options={"comment":"汇付记账表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayTradeRecordRepository")
 */

class HfpayTradeRecord
{
    use Timestamps;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="hfpay_trade_record_id", type="bigint", options={"comment":"汇付记账表"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hfpay_trade_record_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer", nullable=true, options={"comment":"company_id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="string", nullable=true, options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="trade_id", type="string", nullable=true, options={"comment":"业务id"})
     */
    private $trade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="outer_order_id", type="string", nullable=true, options={"comment":"订单id"})
     */
    private $outer_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="form_user_id", type="string", nullable=true, options={"comment":"用户"})
     */
    private $form_user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="target_user_id", type="string", nullable=true, options={"comment":"对象id"})
     */
    private $target_user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="trade_time", type="string", nullable=true, options={"comment":"时间戳"})
     */
    private $trade_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="trade_type", type="integer", nullable=true, options={"comment":"交易类型"})
     */
    private $trade_type;

    /**
     * @var string
     *
     * @ORM\Column(name="fin_type", type="string", nullable=true, options={"comment":"财务科目"})
     */
    private $fin_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="income", type="integer", nullable=true, options={"comment":"收入"})
     */
    private $income;

    /**
     * @var integer
     *
     * @ORM\Column(name="outcome", type="integer", nullable=true, options={"comment":"支出"})
     */
    private $outcome;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_clean", type="integer", nullable=true, options={"comment":"结算状态 0未结算 1已结算", "default": 0})
     */
    private $is_clean = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="clean_time", type="integer", nullable=true, options={"comment":"结算时间"})
     */
    private $clean_time;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", nullable=true, options={"comment":"描述"})
     */
    private $message;

    /**
     * Get hfpayTradeRecord.
     *
     * @return int
     */
    public function getHfpayTradeRecord()
    {
        return $this->hfpay_trade_record_id;
    }

    /**
     * Set companyId.
     *
     * @param int|null $companyId
     *
     * @return HfpayTradeRecord
     */
    public function setCompanyId($companyId = null)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set distributorId.
     *
     * @param string|null $distributorId
     *
     * @return HfpayTradeRecord
     */
    public function setDistributorId($distributorId = null)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return string|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set tradeId.
     *
     * @param string|null $tradeId
     *
     * @return HfpayTradeRecord
     */
    public function setTradeId($tradeId = null)
    {
        $this->trade_id = $tradeId;

        return $this;
    }

    /**
     * Get tradeId.
     *
     * @return string|null
     */
    public function getTradeId()
    {
        return $this->trade_id;
    }

    /**
     * Set outerOrderId.
     *
     * @param string|null $outerOrderId
     *
     * @return HfpayTradeRecord
     */
    public function setOuterOrderId($outerOrderId = null)
    {
        $this->outer_order_id = $outerOrderId;

        return $this;
    }

    /**
     * Get outerOrderId.
     *
     * @return string|null
     */
    public function getOuterOrderId()
    {
        return $this->outer_order_id;
    }

    /**
     * Set formUserId.
     *
     * @param string|null $formUserId
     *
     * @return HfpayTradeRecord
     */
    public function setFormUserId($formUserId = null)
    {
        $this->form_user_id = $formUserId;

        return $this;
    }

    /**
     * Get formUserId.
     *
     * @return string|null
     */
    public function getFormUserId()
    {
        return $this->form_user_id;
    }

    /**
     * Set targetUserId.
     *
     * @param string|null $targetUserId
     *
     * @return HfpayTradeRecord
     */
    public function setTargetUserId($targetUserId = null)
    {
        $this->target_user_id = $targetUserId;

        return $this;
    }

    /**
     * Get targetUserId.
     *
     * @return string|null
     */
    public function getTargetUserId()
    {
        return $this->target_user_id;
    }

    /**
     * Set tradeTime.
     *
     * @param string|null $tradeTime
     *
     * @return HfpayTradeRecord
     */
    public function setTradeTime($tradeTime = null)
    {
        $this->trade_time = $tradeTime;

        return $this;
    }

    /**
     * Get tradeTime.
     *
     * @return string|null
     */
    public function getTradeTime()
    {
        return $this->trade_time;
    }

    /**
     * Set tradeType.
     *
     * @param int|null $tradeType
     *
     * @return HfpayTradeRecord
     */
    public function setTradeType($tradeType = null)
    {
        $this->trade_type = $tradeType;

        return $this;
    }

    /**
     * Get tradeType.
     *
     * @return int|null
     */
    public function getTradeType()
    {
        return $this->trade_type;
    }

    /**
     * Set finType.
     *
     * @param string|null $finType
     *
     * @return HfpayTradeRecord
     */
    public function setFinType($finType = null)
    {
        $this->fin_type = $finType;

        return $this;
    }

    /**
     * Get finType.
     *
     * @return string|null
     */
    public function getFinType()
    {
        return $this->fin_type;
    }

    /**
     * Set income.
     *
     * @param int|null $income
     *
     * @return HfpayTradeRecord
     */
    public function setIncome($income = null)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get income.
     *
     * @return int|null
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * Set outcome.
     *
     * @param int|null $outcome
     *
     * @return HfpayTradeRecord
     */
    public function setOutcome($outcome = null)
    {
        $this->outcome = $outcome;

        return $this;
    }

    /**
     * Get outcome.
     *
     * @return int|null
     */
    public function getOutcome()
    {
        return $this->outcome;
    }

    /**
     * Set isClean.
     *
     * @param int|null $isClean
     *
     * @return HfpayTradeRecord
     */
    public function setIsClean($isClean = null)
    {
        $this->is_clean = $isClean;

        return $this;
    }

    /**
     * Get isClean.
     *
     * @return int|null
     */
    public function getIsClean()
    {
        return $this->is_clean;
    }

    /**
     * Set cleanTime.
     *
     * @param int|null $cleanTime
     *
     * @return HfpayTradeRecord
     */
    public function setCleanTime($cleanTime = null)
    {
        $this->clean_time = $cleanTime;

        return $this;
    }

    /**
     * Get cleanTime.
     *
     * @return int|null
     */
    public function getCleanTime()
    {
        return $this->clean_time;
    }

    /**
     * Set message.
     *
     * @param string|null $message
     *
     * @return HfpayTradeRecord
     */
    public function setMessage($message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get hfpayTradeRecordId.
     *
     * @return int
     */
    public function getHfpayTradeRecordId()
    {
        return $this->hfpay_trade_record_id;
    }
}
