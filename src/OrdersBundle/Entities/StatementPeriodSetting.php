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
use Dingo\Api\Exception\ResourceException;

/**
 * StatementPeriodSetting 结算周期设置
 *
 * @ORM\Table(name="statement_period_setting", options={"comment":"结算周期设置"},
 *     indexes={
 *         @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *     })
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\StatementPeriodSettingRepository")
 */
class StatementPeriodSetting
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
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id"})
     */
    private $merchant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商ID"})
     */
    private $supplier_id;

    /**
     * @var string
     *
     * @ORM\Column(name="period", type="string", options={"comment":"结算周期 day:天 week:周 month:月"})
     */
    private $period;

    /**
     * @var string
     * 
     * distributor 经销商
     * supplier 供应商
     *
     * @ORM\Column(name="merchant_type", type="string", nullable=true, length=30, options={"comment":"商户类型：distributor 经销商,supplier 供应商", "default": "distributor"})
     */
    private $merchant_type = 'distributor';

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return StatementPeriodSetting
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
     * Set merchantId
     *
     * @param integer $merchantId
     *
     * @return StatementPeriodSetting
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId
     *
     * @return integer
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return StatementPeriodSetting
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
     * Set period
     *
     * @param array $period
     *
     * @return StatementPeriodSetting
     */
    public function setPeriod($period)
    {
        if (!is_array($period) || count($period) != 2) {
            throw new ResourceException('结算周期格式错误');
        }

        $this->period = json_encode(array_values($period));

        return $this;
    }

    /**
     * Get period
     *
     * @return array
     */
    public function getPeriod()
    {
        return json_decode($this->period);
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return StatementPeriodSetting
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
     * @param int|null $updated
     *
     * @return StatementPeriodSetting
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

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return StatementPeriodSetting
     */
    public function setSupplierId($supplierId)
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    /**
     * Get supplierId.
     *
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    /**
     * Set merchantType.
     *
     * @param string|null $merchantType
     *
     * @return StatementPeriodSetting
     */
    public function setMerchantType($merchantType = null)
    {
        $this->merchant_type = $merchantType;

        return $this;
    }

    /**
     * Get merchantType.
     *
     * @return string|null
     */
    public function getMerchantType()
    {
        return $this->merchant_type;
    }
}
