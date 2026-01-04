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

namespace DepositBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * RechargeRule 储值规则，充值固定金额送钱或送礼品
 *
 * @ORM\Table(name="deposit_recharge_rule", options={"comment":"储值规则，充值固定金额送钱或送礼品"})
 * @ORM\Entity(repositoryClass="DepositBundle\Repositories\RechargeRuleRepository")
 */
class RechargeRule
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="string", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="money", type="string", options={"comment":"充值固定金额"})
     */
    private $money;

    /**
     * @var string
     *
     * @ORM\Column(name="rule_type", type="string", options={"comment":"充值规则类型"})
     */
    private $rule_type;

    /**
     * @var string
     *
     * @ORM\Column(name="rule_data", type="string", options={"comment":"充值规则数据"})
     */
    private $rule_data;

    /**
     * @var string
     *
     * @ORM\Column(name="create_time", type="string", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param string $companyId
     *
     * @return RechargeRule
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set money
     *
     * @param string $money
     *
     * @return RechargeRule
     */
    public function setMoney($money)
    {
        $this->money = $money;

        return $this;
    }

    /**
     * Get money
     *
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * Set ruleType
     *
     * @param string $ruleType
     *
     * @return RechargeRule
     */
    public function setRuleType($ruleType)
    {
        $this->rule_type = $ruleType;

        return $this;
    }

    /**
     * Get ruleType
     *
     * @return string
     */
    public function getRuleType()
    {
        return $this->rule_type;
    }

    /**
     * Set ruleData
     *
     * @param string $ruleData
     *
     * @return RechargeRule
     */
    public function setRuleData($ruleData)
    {
        $this->rule_data = $ruleData;

        return $this;
    }

    /**
     * Get ruleData
     *
     * @return string
     */
    public function getRuleData()
    {
        return $this->rule_data;
    }

    /**
     * Set createTime
     *
     * @param string $createTime
     *
     * @return RechargeRule
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return string
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }
}
