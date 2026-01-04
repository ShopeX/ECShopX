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
 * HfpayMerchantPayment 汇付平台转账记录表
 *
 * @ORM\Table(name="hfpay_merchant_payment", options={"comment":"汇付平台转账记录表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_rel_scene_id_rel_scene_name", columns={"rel_scene_id","rel_scene_name"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayMerchantPaymentRepository")
 */

class HfpayMerchantPayment
{
    use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="hfpay_merchant_payment_id", type="bigint", options={"comment":"汇付取现银行卡表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hfpay_merchant_payment_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rel_scene_id", type="bigint", options={"comment":"业务场景值id"})
     */
    private $rel_scene_id;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_scene_name", type="string", options={"comment":"业务场景值名称"})
     */
    private $rel_scene_name;

    /**
     * @var string
     *
     * @ORM\Column(name="mer_cust_id", type="string", options={"comment":"汇付平台客户号"})
     */
    private $mer_cust_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_cust_id", type="string", options={"comment":"汇付客户号"})
     */
    private $user_cust_id;

    /**
     * @var string
     *
     * @ORM\Column(name="acct_id", type="string", options={"comment":"汇付账户号"})
     */
    private $acct_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="trans_amt", type="bigint", options={"comment":"转账金额"})
     */
    private $trans_amt;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"状态 0 未提交 1转账成功 2转账失败", "default": 0})
     */
    private $status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_id", type="string", nullable=true, options={"comment":"汇付接口请求order_id"})
     */
    private $hf_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_date", type="string",nullable=true, options={"comment":"汇付接口请求order_date"})
     */
    private $hf_order_date;

    /**
     * @var string
     *
     * @ORM\Column(name="resp_code", type="string", nullable=true, options={"comment":"汇付接口返回码"})
     */
    private $resp_code;

    /**
     * @var string
     *
     * @ORM\Column(name="resp_desc", type="string", nullable=true, options={"comment":"汇付接口返回码描述"})
     */
    private $resp_desc;


    /**
     * Get hfpayMerchantPaymentId.
     *
     * @return int
     */
    public function getHfpayMerchantPaymentId()
    {
        return $this->hfpay_merchant_payment_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return HfpayMerchantPayment
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
     * Set relSceneId.
     *
     * @param int $relSceneId
     *
     * @return HfpayMerchantPayment
     */
    public function setRelSceneId($relSceneId)
    {
        $this->rel_scene_id = $relSceneId;

        return $this;
    }

    /**
     * Get relSceneId.
     *
     * @return int
     */
    public function getRelSceneId()
    {
        return $this->rel_scene_id;
    }

    /**
     * Set merCustId.
     *
     * @param string $merCustId
     *
     * @return HfpayMerchantPayment
     */
    public function setMerCustId($merCustId)
    {
        $this->mer_cust_id = $merCustId;

        return $this;
    }

    /**
     * Get merCustId.
     *
     * @return string
     */
    public function getMerCustId()
    {
        return $this->mer_cust_id;
    }

    /**
     * Set userCustId.
     *
     * @param string $userCustId
     *
     * @return HfpayMerchantPayment
     */
    public function setUserCustId($userCustId)
    {
        $this->user_cust_id = $userCustId;

        return $this;
    }

    /**
     * Get userCustId.
     *
     * @return string
     */
    public function getUserCustId()
    {
        return $this->user_cust_id;
    }

    /**
     * Set acctId.
     *
     * @param string $acctId
     *
     * @return HfpayMerchantPayment
     */
    public function setAcctId($acctId)
    {
        $this->acct_id = $acctId;

        return $this;
    }

    /**
     * Get acctId.
     *
     * @return string
     */
    public function getAcctId()
    {
        return $this->acct_id;
    }

    /**
     * Set transAmt.
     *
     * @param int $transAmt
     *
     * @return HfpayMerchantPayment
     */
    public function setTransAmt($transAmt)
    {
        $this->trans_amt = $transAmt;

        return $this;
    }

    /**
     * Get transAmt.
     *
     * @return int
     */
    public function getTransAmt()
    {
        return $this->trans_amt;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return HfpayMerchantPayment
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
     * Set hfOrderId.
     *
     * @param string|null $hfOrderId
     *
     * @return HfpayMerchantPayment
     */
    public function setHfOrderId($hfOrderId = null)
    {
        $this->hf_order_id = $hfOrderId;

        return $this;
    }

    /**
     * Get hfOrderId.
     *
     * @return string|null
     */
    public function getHfOrderId()
    {
        return $this->hf_order_id;
    }

    /**
     * Set hfOrderDate.
     *
     * @param string|null $hfOrderDate
     *
     * @return HfpayMerchantPayment
     */
    public function setHfOrderDate($hfOrderDate = null)
    {
        $this->hf_order_date = $hfOrderDate;

        return $this;
    }

    /**
     * Get hfOrderDate.
     *
     * @return string|null
     */
    public function getHfOrderDate()
    {
        return $this->hf_order_date;
    }

    /**
     * Set respCode.
     *
     * @param string|null $respCode
     *
     * @return HfpayMerchantPayment
     */
    public function setRespCode($respCode = null)
    {
        $this->resp_code = $respCode;

        return $this;
    }

    /**
     * Get respCode.
     *
     * @return string|null
     */
    public function getRespCode()
    {
        return $this->resp_code;
    }

    /**
     * Set respDesc.
     *
     * @param string|null $respDesc
     *
     * @return HfpayMerchantPayment
     */
    public function setRespDesc($respDesc = null)
    {
        $this->resp_desc = $respDesc;

        return $this;
    }

    /**
     * Get respDesc.
     *
     * @return string|null
     */
    public function getRespDesc()
    {
        return $this->resp_desc;
    }

    /**
     * Set relSceneName.
     *
     * @param string $relSceneName
     *
     * @return HfpayMerchantPayment
     */
    public function setRelSceneName($relSceneName)
    {
        $this->rel_scene_name = $relSceneName;

        return $this;
    }

    /**
     * Get relSceneName.
     *
     * @return string
     */
    public function getRelSceneName()
    {
        return $this->rel_scene_name;
    }
}
