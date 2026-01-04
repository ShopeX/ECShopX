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

namespace SupplierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SupplierItemsCommission 商品佣金费率
 *
 * @ORM\Table(name="supplier_items_commission", options={"comment":"商品佣金费率"},
 *     indexes={
 *         @ORM\Index(name="idx_goods_id", columns={"goods_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="SupplierBundle\Repositories\ItemsCommissionRepository")
 */
class SupplierItemsCommission
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"商户id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", nullable=true, options={"comment":"产品ID", "default":0})
     */
    private $goods_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id", "default":0})
     */
    private $supplier_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="commission_ratio", type="integer", options={"comment":"佣金比例", "default": 0})
     */
    private $commission_ratio = 0;

    /**
     * @var \DateTime $add_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true, options={"comment":"创建时间"})
     */
    private $add_time;

    /**
     * @var \DateTime $modify_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true, options={"comment":"更新时间"})
     */
    private $modify_time;
    

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
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return ItemsCommission
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set goodsId.
     *
     * @param int|null $goodsId
     *
     * @return ItemsCommission
     */
    public function setGoodsId($goodsId = null)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int|null
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return ItemsCommission
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
     * Set commissionRatio.
     *
     * @param int $commissionRatio
     *
     * @return ItemsCommission
     */
    public function setCommissionRatio($commissionRatio)
    {
        $this->commission_ratio = $commissionRatio;

        return $this;
    }

    /**
     * Get commissionRatio.
     *
     * @return int
     */
    public function getCommissionRatio()
    {
        return $this->commission_ratio;
    }

    /**
     * Set addTime.
     *
     * @param \DateTime|null $addTime
     *
     * @return ItemsCommission
     */
    public function setAddTime($addTime = null)
    {
        $this->add_time = $addTime;

        return $this;
    }

    /**
     * Get addTime.
     *
     * @return \DateTime|null
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * Set modifyTime.
     *
     * @param \DateTime|null $modifyTime
     *
     * @return ItemsCommission
     */
    public function setModifyTime($modifyTime = null)
    {
        $this->modify_time = $modifyTime;

        return $this;
    }

    /**
     * Get modifyTime.
     *
     * @return \DateTime|null
     */
    public function getModifyTime()
    {
        return $this->modify_time;
    }
}
