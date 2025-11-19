<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemsProfit 商品分润配置表
 *
 * @ORM\Table(name="items_profit", options={"comment"="商品分润配置表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsProfitRepository")
 */
class ItemsProfit
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品ID"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_type", nullable=true, type="string", options={"comment":"分佣计算方式 0商品不设置默认分润,1按照比例分润,2按照填写金额分润", "default": 0})
     */
    private $profit_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_conf", nullable=true, type="json_array", options={"comment":"分销配置"})
     */
    private $profit_conf;

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return ItemsProfit
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ItemsProfit
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
     * Set profitType.
     *
     * @param string|null $profitType
     *
     * @return ItemsProfit
     */
    public function setProfitType($profitType = null)
    {
        $this->profit_type = $profitType;

        return $this;
    }

    /**
     * Get profitType.
     *
     * @return string|null
     */
    public function getProfitType()
    {
        return $this->profit_type;
    }

    /**
     * Set profitConf.
     *
     * @param array|null $profitConf
     *
     * @return ItemsProfit
     */
    public function setProfitConf($profitConf = null)
    {
        $this->profit_conf = $profitConf;

        return $this;
    }

    /**
     * Get profitConf.
     *
     * @return array|null
     */
    public function getProfitConf()
    {
        return $this->profit_conf;
    }
}
