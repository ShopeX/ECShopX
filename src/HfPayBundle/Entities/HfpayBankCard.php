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
 * HfpayBank 汇付取现银行卡表
 *
 * @ORM\Table(name="hfpay_bank_card", options={"comment":"汇付取现银行卡表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayBankCardRepository")
 */

class HfpayBankCard
{
    use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="hfpay_bank_card_id", type="bigint", options={"comment":"汇付取现银行卡表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hfpay_bank_card_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"分销商id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_cust_id", type="string", nullable=true, options={"comment":"用户客户号"})
     */
    private $user_cust_id;

    /**
     * @var string
     *
     * 0 对公
     * 1 对私
     *
     * @ORM\Column(name="card_type", type="string", nullable=true, options={"comment":"绑卡类型"})
     */
    private $card_type;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_id", type="string", nullable=true, options={"comment":"银行代号"})
     */
    private $bank_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", nullable=true, options={"comment":"银行名称"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="card_num", type="string", nullable=true, options={"comment":"银行卡号"})
     */
    private $card_num;

    /**
     * @var string
     *
     * @ORM\Column(name="bind_card_id", type="string", nullable=true, options={"comment":"汇付绑定id"})
     */
    private $bind_card_id;

    /**
     * @var string
     *
     * 1 取现卡
     * 2 非取现卡
     *
     * @ORM\Column(name="is_cash", type="string", nullable=true, options={"default": 1, "comment":"是否取现卡"})
     */
    private $is_cash;

    /**
     * Get hfpayBankCardId.
     *
     * @return int
     */
    public function getHfpayBankCardId()
    {
        return $this->hfpay_bank_card_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return HfpayBankCard
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
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return HfpayBankCard
     */
    public function setDistributorId($distributorId = null)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return HfpayBankCard
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set userCustId.
     *
     * @param string|null $userCustId
     *
     * @return HfpayBankCard
     */
    public function setUserCustId($userCustId = null)
    {
        $this->user_cust_id = $userCustId;

        return $this;
    }

    /**
     * Get userCustId.
     *
     * @return string|null
     */
    public function getUserCustId()
    {
        return $this->user_cust_id;
    }

    /**
     * Set cardType.
     *
     * @param string|null $cardType
     *
     * @return HfpayBankCard
     */
    public function setCardType($cardType = null)
    {
        $this->card_type = $cardType;

        return $this;
    }

    /**
     * Get cardType.
     *
     * @return string|null
     */
    public function getCardType()
    {
        return $this->card_type;
    }

    /**
     * Set bankId.
     *
     * @param string|null $bankId
     *
     * @return HfpayBankCard
     */
    public function setBankId($bankId = null)
    {
        $this->bank_id = $bankId;

        return $this;
    }

    /**
     * Get bankId.
     *
     * @return string|null
     */
    public function getBankId()
    {
        return $this->bank_id;
    }

    /**
     * Set bankName.
     *
     * @param string|null $bankName
     *
     * @return HfpayBankCard
     */
    public function setBankName($bankName = null)
    {
        $this->bank_name = $bankName;

        return $this;
    }

    /**
     * Get bankName.
     *
     * @return string|null
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Set cardNum.
     *
     * @param string|null $cardNum
     *
     * @return HfpayBankCard
     */
    public function setCardNum($cardNum = null)
    {
        $this->card_num = $cardNum;

        return $this;
    }

    /**
     * Get cardNum.
     *
     * @return string|null
     */
    public function getCardNum()
    {
        return $this->card_num;
    }

    /**
     * Set bindCardId.
     *
     * @param string|null $bindCardId
     *
     * @return HfpayBankCard
     */
    public function setBindCardId($bindCardId = null)
    {
        $this->bind_card_id = $bindCardId;

        return $this;
    }

    /**
     * Get bindCardId.
     *
     * @return string|null
     */
    public function getBindCardId()
    {
        return $this->bind_card_id;
    }

    /**
     * Set isCash.
     *
     * @param string|null $isCash
     *
     * @return HfpayBankCard
     */
    public function setIsCash($isCash = null)
    {
        $this->is_cash = $isCash;

        return $this;
    }

    /**
     * Get isCash.
     *
     * @return string|null
     */
    public function getIsCash()
    {
        return $this->is_cash;
    }
}
