<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ActivityGoods 员工内购商品表
 *
 * @ORM\Table(name="employee_purchase_activity_goods", options={"comment"="员工内购商品表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="idx_goods_id", columns={"goods_id"}),
 * })
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\ActivityGoodsRepository")
 */
class ActivityGoods
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"商品ID"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * Set activityId.
     *
     * @param int $activityId
     *
     * @return ActivityGoods
     */
    public function setActivityId($activityId)
    {
        // ShopEx EcShopX Service Component
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId.
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return ActivityGoods
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ActivityGoods
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
}
