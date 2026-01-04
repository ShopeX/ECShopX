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

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PackagePromotions 组合促销促销规则表
 *
 * @ORM\Table(name="promotions_package", options={"comment"="组合促销促销规则表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_created", columns={"created"}),
 *    @ORM\Index(name="idx_package_status", columns={"package_status"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PackageRepository")
 */
class PackagePromotions
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="package_id", type="bigint", options={"comment":"组合促销规则id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $package_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"商品id"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="main_item_id", type="bigint", options={"comment":"主商品id"})
     */
    private $main_item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="main_item_price", type="bigint", options={"comment":"主商品价格"})
     */
    private $main_item_price;

    /**
     * @var string
     *
     * @ORM\Column(name="package_name", type="string", length=50, options={"comment":"组合促销名称"})
     */
    private $package_name;

    /**
     * @var string
     *
     * @ORM\Column(name="valid_grade", type="string", options={"comment":"会员级别集合"})
     */
    private $valid_grade;

    /**
     * @var string
     *
     * @ORM\Column(name="used_platform", type="integer", nullable=true, options={"comment":"0 商家全场可用|1 只能用于pc|2 只能用于wap|3 只能用于app, 使用平台", "default": 0})
     */
    private $used_platform;

    /**
     * @var boolean
     *
     * @ORM\Column(name="free_postage", type="boolean", nullable=true, options={"comment":"0 包邮|1 商品", "default": 0})
     */
    private $free_postage;

    /**
     * @var integer
     *
     * @ORM\Column(name="package_total_price", nullable=true, type="integer", options={"comment":"组合促销各商品总价", "default": 0})
     */
    private $package_total_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"起始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"截止时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="package_status", nullable=true, type="string", options={"comment":"NO_REVIEWED 未审核|PENDING 待审核|AGREE 审核通过|REFUSE 审核拒绝|CANCEL 已取消", "default":"NO_REVIEWED"})
     *
     */
    private $package_status;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", nullable=true, type="string", options={"comment":"审核不通过原因", "default": ""})
     *
     */
    private $reason;

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
     * @var string
     *
     * @ORM\Column(name="source_type", type="string", length=20, nullable=true, options={"comment":"添加者类型：distributor"})
     */
    private $source_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", nullable=true, options={"comment":"添加者ID: 如店铺ID", "default":0})
     */
    private $source_id = 0;

    /**
     * Get packageId
     *
     * @return integer
     */
    public function getPackageId()
    {
        return $this->package_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PackagePromotions
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
     * Set packageName
     *
     * @param string $packageName
     *
     * @return PackagePromotions
     */
    public function setPackageName($packageName)
    {
        $this->package_name = $packageName;

        return $this;
    }

    /**
     * Get packageName
     *
     * @return string
     */
    public function getPackageName()
    {
        return $this->package_name;
    }

    /**
     * Set validGrade
     *
     * @param string $validGrade
     *
     * @return PackagePromotions
     */
    public function setValidGrade($validGrade)
    {
        $this->valid_grade = $validGrade;

        return $this;
    }

    /**
     * Get validGrade
     *
     * @return string
     */
    public function getValidGrade()
    {
        return $this->valid_grade;
    }

    /**
     * Set usedPlatform
     *
     * @param integer $usedPlatform
     *
     * @return PackagePromotions
     */
    public function setUsedPlatform($usedPlatform)
    {
        $this->used_platform = $usedPlatform;

        return $this;
    }

    /**
     * Get usedPlatform
     *
     * @return integer
     */
    public function getUsedPlatform()
    {
        return $this->used_platform;
    }

    /**
     * Set freePostage
     *
     * @param boolean $freePostage
     *
     * @return PackagePromotions
     */
    public function setFreePostage($freePostage)
    {
        $this->free_postage = $freePostage;

        return $this;
    }

    /**
     * Get freePostage
     *
     * @return boolean
     */
    public function getFreePostage()
    {
        return $this->free_postage;
    }

    /**
     * Set packageTotalPrice
     *
     * @param integer $packageTotalPrice
     *
     * @return PackagePromotions
     */
    public function setPackageTotalPrice($packageTotalPrice)
    {
        $this->package_total_price = $packageTotalPrice;

        return $this;
    }

    /**
     * Get packageTotalPrice
     *
     * @return integer
     */
    public function getPackageTotalPrice()
    {
        return $this->package_total_price;
    }

    /**
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return PackagePromotions
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return PackagePromotions
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set packageStatus
     *
     * @param string $packageStatus
     *
     * @return PackagePromotions
     */
    public function setPackageStatus($packageStatus)
    {
        $this->package_status = $packageStatus;

        return $this;
    }

    /**
     * Get packageStatus
     *
     * @return string
     */
    public function getPackageStatus()
    {
        return $this->package_status;
    }

    /**
     * Set reason
     *
     * @param string $reason
     *
     * @return PackagePromotions
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return PackagePromotions
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
     * @return PackagePromotions
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
     * Set mainItemId
     *
     * @param integer $mainItemId
     *
     * @return PackagePromotions
     */
    public function setMainItemId($mainItemId)
    {
        $this->main_item_id = $mainItemId;

        return $this;
    }

    /**
     * Get mainItemId
     *
     * @return integer
     */
    public function getMainItemId()
    {
        return $this->main_item_id;
    }

    /**
     * Set mainItemPrice
     *
     * @param integer $mainItemPrice
     *
     * @return PackagePromotions
     */
    public function setMainItemPrice($mainItemPrice)
    {
        $this->main_item_price = $mainItemPrice;

        return $this;
    }

    /**
     * Get mainItemPrice
     *
     * @return integer
     */
    public function getMainItemPrice()
    {
        return $this->main_item_price;
    }

    /**
     * Set goodsId
     *
     * @param integer $goodsId
     *
     * @return PackagePromotions
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId
     *
     * @return integer
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set sourceType.
     *
     * @param string|null $sourceType
     *
     * @return MarketingActivity
     */
    public function setSourceType($sourceType = null)
    {
        $this->source_type = $sourceType;

        return $this;
    }

    /**
     * Get sourceType.
     *
     * @return string|null
     */
    public function getSourceType()
    {
        return $this->source_type;
    }

    /**
     * Set sourceId.
     *
     * @param int|null $sourceId
     *
     * @return MarketingActivity
     */
    public function setSourceId($sourceId = null)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId.
     *
     * @return int|null
     */
    public function getSourceId()
    {
        return $this->source_id;
    }
}
