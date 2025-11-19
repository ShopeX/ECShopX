<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
