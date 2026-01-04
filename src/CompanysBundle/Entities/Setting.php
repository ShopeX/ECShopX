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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Setting 企业设置
 *
 * @ORM\Table(name="companys_setting", options={"comment":"企业设置表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\SettingRepository")
 */
class Setting
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="community_config", nullable=true,  type="text", options={"comment":"社区相关设置"})
     */
    private $community_config;

    /**
     * @var string
     *
     * @ORM\Column(name="withdraw_bank", nullable=true,  type="text", options={"comment":"提现支持银行类型"})
     */
    private $withdraw_bank;

    /**
     * @var integer
     *
     * @ORM\Column(name="consumer_hotline", nullable=true, type="string", options={"comment":"客服电话"})
     */
    private $consumer_hotline;

    /**
     * @var integer
     *
     * @ORM\Column(name="customer_switch", type="integer", options={"comment":"客服开关", "default":0})
     */
    private $customer_switch;

    /**
     * @var string
     *
     * @ORM\Column(name="fapiao_config", nullable=true,  type="text", options={"comment":"发票相关设置"})
     */
    private $fapiao_config;

    /**
     * @var integer
     *
     * @ORM\Column(name="fapiao_switch", type="integer", options={"comment":"发票开关", "default":0})
     */
    private $fapiao_switch = 0;


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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Setting
     */
    public function setCompanyId($companyId)
    {
        // ShopEx EcShopX Core Module
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
     * Set communityConfig
     *
     * @param string $communityConfig
     *
     * @return Setting
     */
    public function setCommunityConfig($communityConfig)
    {
        $this->community_config = $communityConfig;

        return $this;
    }

    /**
     * Get communityConfig
     *
     * @return string
     */
    public function getCommunityConfig()
    {
        return $this->community_config;
    }

    /**
     * Set withdrawBank
     *
     * @param string $withdrawBank
     *
     * @return Setting
     */
    public function setWithdrawBank($withdrawBank)
    {
        $this->withdraw_bank = $withdrawBank;

        return $this;
    }

    /**
     * Get withdrawBank
     *
     * @return string
     */
    public function getWithdrawBank()
    {
        return $this->withdraw_bank;
    }

    /**
     * Set consumerHotline
     *
     * @param integer $consumerHotline
     *
     * @return Setting
     */
    public function setConsumerHotline($consumerHotline)
    {
        $this->consumer_hotline = $consumerHotline;

        return $this;
    }

    /**
     * Get consumerHotline
     *
     * @return integer
     */
    public function getConsumerHotline()
    {
        return $this->consumer_hotline;
    }


    /**
     * Set fapiaoConfig
     *
     * @param string $fapiaoConfig
     *
     * @return Setting
     */
    public function setFapiaoConfig($fapiaoConfig)
    {
        $this->fapiao_config = $fapiaoConfig;

        return $this;
    }

    /**
     * Get fapiaoConfig
     *
     * @return string
     */
    public function getFapiaoConfig()
    {
        return $this->fapiao_config;
    }

    /**
     * Set fapiaoSwitch
     *
     * @param integer $fapiaoSwitch
     *
     * @return Setting
     */
    public function setFapiaoSwitch($fapiaoSwitch)
    {
        $this->fapiao_switch = $fapiaoSwitch;

        return $this;
    }

    /**
     * Get fapiaoSwitch
     *
     * @return integer
     */
    public function getFapiaoSwitch()
    {
        return $this->fapiao_switch;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Setting
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
     * @return Setting
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
     * Set customerSwitch
     *
     * @param integer $customerSwitch
     *
     * @return Setting
     */
    public function setCustomerSwitch($customerSwitch)
    {
        $this->customer_switch = $customerSwitch;

        return $this;
    }

    /**
     * Get customerSwitch
     *
     * @return integer
     */
    public function getCustomerSwitch()
    {
        return $this->customer_switch;
    }
}
