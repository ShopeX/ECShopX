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

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemsCommission 商品佣金配置表
 *
 * @ORM\Table(name="items_commission", options={"comment"="商品佣金配置表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_rel_id", columns={"rel_id"}),
 *    @ORM\Index(name="ix_type", columns={"type"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsCommissionRepository")
 */
class ItemsCommission
{

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     * @ORM\Column(name="rel_id", type="bigint", options={"comment":"关联ID"})
     */
    private $rel_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", nullable=true, type="string", options={"comment":"数据类型 goods:SPU,item:SKU", "default": "goods"})
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="commission_type", nullable=true, type="string", options={"comment":"佣金计算方式 1:按照比例,2:按照填写金额", "default": 1})
     */
    private $commission_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="commission_conf", nullable=true, type="json_array", options={"comment":"佣金"})
     */
    private $commission_conf;

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
     * @return ItemsCommission
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
     * Set relId.
     *
     * @param int $relId
     *
     * @return ItemsCommission
     */
    public function setRelId($relId)
    {
        $this->rel_id = $relId;

        return $this;
    }

    /**
     * Get relId.
     *
     * @return int
     */
    public function getRelId()
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        return $this->rel_id;
    }

    /**
     * Set type.
     *
     * @param string|null $type
     *
     * @return ItemsCommission
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set commissionType.
     *
     * @param string|null $commissionType
     *
     * @return ItemsCommission
     */
    public function setCommissionType($commissionType = null)
    {
        $this->commission_type = $commissionType;

        return $this;
    }

    /**
     * Get commissionType.
     *
     * @return string|null
     */
    public function getCommissionType()
    {
        return $this->commission_type;
    }

    /**
     * Set commissionConf.
     *
     * @param array|null $commissionConf
     *
     * @return ItemsCommission
     */
    public function setCommissionConf($commissionConf = null)
    {
        $this->commission_conf = $commissionConf;

        return $this;
    }

    /**
     * Get commissionConf.
     *
     * @return array|null
     */
    public function getCommissionConf()
    {
        return $this->commission_conf;
    }
}
