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

/**
 * CompanyRelLogistics 商城物流启用表
 *
 * @ORM\Table(name="company_rel_logistics", options={"comment":"商城物流启用表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\CompanyRelLogisticsRepository")
 */
class CompanyRelLogistics
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="corp_id", type="smallint", options={"comment":"物流公司ID"})
     *
     *
     */

    private $corp_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="smallint", options={"comment":"公司ID"})
     *
     *
     */

    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="corp_code", type="string", options={"comment":"物流公司代码"})
     *
     */

    private $corp_code;

    /**
     * @var string
     *
     * @ORM\Column(name="kuaidi_code", type="string", options={"comment":"快递100代码"})
     *
     */
    private $kuaidi_code;

    /**
     * @var string
     *
     * @ORM\Column(name="corp_name", type="string", options={"comment":"物流公司简称"})
     */
    private $corp_name;

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
     * Get id
     *
     * @return \int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set corpId
     *
     * @param integer $corpId
     *
     * @return ShopRelLogistics
     */
    public function setCorpId($corpId)
    {
        $this->corp_id = $corpId;

        return $this;
    }

    /**
     * Get corpId
     *
     * @return integer
     */
    public function getCorpId()
    {
        return $this->corp_id;
    }


    /**
     * Set corpCode
     *
     * @param string $corpCode
     *
     * @return ShopRelLogistics
     */
    public function setCorpCode($corpCode)
    {
        $this->corp_code = $corpCode;

        return $this;
    }

    /**
     * Get corpCode
     *
     * @return string
     */
    public function getCorpCode()
    {
        return $this->corp_code;
    }

    /**
     * Set corpName
     *
     * @param string $corpName
     *
     * @return ShopRelLogistics
     */
    public function setCorpName($corpName)
    {
        $this->corp_name = $corpName;

        return $this;
    }

    /**
     * Get corpName
     *
     * @return string
     */
    public function getCorpName()
    {
        return $this->corp_name;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return CompanyRelLogistics
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
     * Set kuaidiCode
     *
     * @param string $kuaidiCode
     *
     * @return CompanyRelLogistics
     */
    public function setKuaidiCode($kuaidiCode)
    {
        $this->kuaidi_code = $kuaidiCode;

        return $this;
    }

    /**
     * Get kuaidiCode
     *
     * @return string
     */
    public function getKuaidiCode()
    {
        return $this->kuaidi_code;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return CompanyRelLogistics
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
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
     * @return CompanyRelLogistics
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
}
