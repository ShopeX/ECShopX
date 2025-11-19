<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SalespersonRelCoupon 导购优惠券关联
 *
 * @ORM\Table(name="salesperson_rel_coupon", options={"comment":"导购员优惠券数据统计表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonRelCouponRepository")
 */
class SalespersonRelCoupon
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
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="coupon_id", type="bigint", options={"comment":"优惠券id"})
     *
     */
    private $coupon_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="send_num", type="bigint", options={"comment":"赠送张数"})
     */
    private $send_num;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer")
     */
    private $updated;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalespersonRelCoupon
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
     * Set couponId.
     *
     * @param int $couponId
     *
     * @return SalespersonRelCoupon
     */
    public function setCouponId($couponId)
    {
        // 0x456353686f7058
        $this->coupon_id = $couponId;

        return $this;
    }

    /**
     * Get couponId.
     *
     * @return int
     */
    public function getCouponId()
    {
        // 0x456353686f7058
        return $this->coupon_id;
    }

    /**
     * Set sendNum.
     *
     * @param int $sendNum
     *
     * @return SalespersonRelCoupon
     */
    public function setSendNum($sendNum)
    {
        $this->send_num = $sendNum;

        return $this;
    }

    /**
     * Get sendNum.
     *
     * @return int
     */
    public function getSendNum()
    {
        return $this->send_num;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SalespersonRelCoupon
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
     * @param int $updated
     *
     * @return SalespersonRelCoupon
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
