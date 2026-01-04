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
 * ShippingTemplates 运费模板表
 *
 * @ORM\Table(name="shipping_templates", options={"comment":"运费模板表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\ShippingTemplatesRepository")
 */
class ShippingTemplates
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="template_id", type="bigint", options={"comment":"运费模板id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $template_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"商家id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, options={"default":"", "comment":"运费模板名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="is_free", type="string", options={"default":0, "comment":"是否包邮"})
     */
    private $is_free;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"unsigned":true, "default":0, "comment":"供应商id"})
     */
    private $supplier_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="valuation", type="string", length=1, options={"default":1, "comment":"运费计算参数来源"})
     */
    private $valuation;

    /**
     * @var boolean
     *
     * @ORM\Column(name="protect", nullable=true, type="boolean", options={"default":false, "comment":"物流保价"})
     */
    private $protect;

    /**
     * @var double
     *
     * @ORM\Column(name="protect_rate", nullable=true, type="decimal", precision=6, scale=3, options={"default":"0.000", "comment":"保价费率"})
     */
    private $protect_rate;

    /**
     * @var double
     *
     * @ORM\Column(name="minprice", nullable=true, type="decimal", precision=10, scale=2, options={"default":"0.00", "comment":"保价费最低值"})
     */
    private $minprice;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", options={"default":true, "comment":"是否开启"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_conf", nullable=true, type="text", options={"comment":"运费模板中运费信息对象，包含默认运费和指定地区运费"})
     */
    private $fee_conf;

    /**
     * @var string
     *
     * @ORM\Column(name="nopost_conf", nullable=true, type="text", options={"default": "[]", "comment":"不包邮地区"})
     */
    private $nopost_conf;

    /**
     * @var string
     *
     * @ORM\Column(name="free_conf", nullable=true, type="text", options={"comment":"指定包邮的条件"})
     */
    private $free_conf;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"最后修改时间"})
     */
    private $update_time;

    /**
     * Get templateId
     *
     * @return integer
     */
    public function getTemplateId()
    {
        return $this->template_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ShippingTemplates
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
     * Set name
     *
     * @param string $name
     *
     * @return ShippingTemplates
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set isFree
     *
     * @param string $isFree
     *
     * @return ShippingTemplates
     */
    public function setIsFree($isFree)
    {
        $this->is_free = $isFree;

        return $this;
    }

    /**
     * Get isFree
     *
     * @return string
     */
    public function getIsFree()
    {
        return $this->is_free;
    }

    /**
     * Set valuation
     *
     * @param string $valuation
     *
     * @return ShippingTemplates
     */
    public function setValuation($valuation)
    {
        $this->valuation = $valuation;

        return $this;
    }

    /**
     * Get valuation
     *
     * @return string
     */
    public function getValuation()
    {
        return $this->valuation;
    }

    /**
     * Set protect
     *
     * @param boolean $protect
     *
     * @return ShippingTemplates
     */
    public function setProtect($protect)
    {
        $this->protect = $protect;

        return $this;
    }

    /**
     * Get protect
     *
     * @return boolean
     */
    public function getProtect()
    {
        return $this->protect;
    }

    /**
     * Set protectRate
     *
     * @param string $protectRate
     *
     * @return ShippingTemplates
     */
    public function setProtectRate($protectRate)
    {
        $this->protect_rate = $protectRate;

        return $this;
    }

    /**
     * Get protectRate
     *
     * @return string
     */
    public function getProtectRate()
    {
        return $this->protect_rate;
    }

    /**
     * Set minprice
     *
     * @param string $minprice
     *
     * @return ShippingTemplates
     */
    public function setMinprice($minprice)
    {
        $this->minprice = $minprice;

        return $this;
    }

    /**
     * Get minprice
     *
     * @return string
     */
    public function getMinprice()
    {
        return $this->minprice;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return ShippingTemplates
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set feeConf
     *
     * @param string $feeConf
     *
     * @return ShippingTemplates
     */
    public function setFeeConf($feeConf)
    {
        $this->fee_conf = $feeConf;

        return $this;
    }

    /**
     * Get feeConf
     *
     * @return string
     */
    public function getFeeConf()
    {
        return $this->fee_conf;
    }

    /**
     * Set freeConf
     *
     * @param string $freeConf
     *
     * @return ShippingTemplates
     */
    public function setFreeConf($freeConf)
    {
        $this->free_conf = $freeConf;

        return $this;
    }

    /**
     * Get freeConf
     *
     * @return string
     */
    public function getFreeConf()
    {
        return $this->free_conf;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return ShippingTemplates
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return integer
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime
     *
     * @param integer $updateTime
     *
     * @return ShippingTemplates
     */
    public function setUpdateTime($updateTime)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return integer
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return ShippingTemplates
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set supplierId
     *
     * @param integer $supplierId
     *
     * @return ShippingTemplates
     */
    public function setSupplierId($supplierId)
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    /**
     * Get supplierId
     *
     * @return integer
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    /**
     * Set nopostConf
     *
     * @param string $nopostConf
     *
     * @return ShippingTemplates
     */
    public function setNopostConf($nopostConf)
    {
        $this->nopost_conf = $nopostConf;

        return $this;
    }

    /**
     * Get nopostConf
     *
     * @return string
     */
    public function getNopostConf()
    {
        return $this->nopost_conf;
    }
}
