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

/**
 * Resources 资源包表
 *
 * @ORM\Table(name="resources", options={"comment":"资源包表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\ResourcesRepository")
 */
class Resources
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="resource_id", type="bigint", options={"comment":"资源包id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $resource_id;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_name", type="string", options={"comment":"资源包名称"})
     */
    private $resource_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="eid", type="string", nullable=true, options={"comment":"企业id"})
     */
    private $eid;

    /**
     * @var string
     *
     * @ORM\Column(name="passport_uid", nullable=true, type="string")
     */
    private $passport_uid;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_num", type="integer", options={"comment":"资源门店数"})
     */
    private $shop_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="left_shop_num", type="integer", options={"comment":"可用门店数"})
     */
    private $left_shop_num;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", options={"comment":"资源来源。demo:开通试用,purchased:购买,gift:赠品"})
     */
    private $source;

    /**
     * @var integer
     *
     * @ORM\Column(name="available_days", type="integer", options={"comment":"可用天数"})
     */
    private $available_days;

    /**
     * @var integer
     *
     * @ORM\Column(name="active_at", type="bigint", options={"comment":"激活时间"})
     */
    private $active_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="expired_at", type="bigint", options={"comment":"过期时间"})
     */
    private $expired_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="active_code", nullable=true, type="string", length=255, options={"comment":"激活码"})
     */
    private $active_code;

    /**
     * @var string
     *
     * @ORM\Column(name="issue_id", nullable=true, type="string", length=50, options={"comment":"在线开通工单号"})
     */
    private $issue_id;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_code", nullable=true, type="string", length=50, options={"comment":"商品code"})
     */
    private $goods_code;

    /**
     * @var string
     *
     * @ORM\Column(name="product_code", nullable=true, type="string", length=50, options={"comment":"基础系统code"})
     */
    private $product_code;

    /**
     * Get resourceId
     *
     * @return integer
     */
    public function getResourceId()
    {
        // ShopEx EcShopX Core Module
        return $this->resource_id;
    }

    /**
     * Set resourceName
     *
     * @param string $resourceName
     *
     * @return Resources
     */
    public function setResourceName($resourceName)
    {
        $this->resource_name = $resourceName;

        return $this;
    }

    /**
     * Get resourceName
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Resources
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
     * Set eid
     *
     * @param string $eid
     *
     * @return Resources
     */
    public function setEid($eid)
    {
        $this->eid = $eid;

        return $this;
    }

    /**
     * Get eid
     *
     * @return string
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * Set passportUid
     *
     * @param string $passportUid
     *
     * @return Resources
     */
    public function setPassportUid($passportUid)
    {
        $this->passport_uid = $passportUid;

        return $this;
    }

    /**
     * Get passportUid
     *
     * @return string
     */
    public function getPassportUid()
    {
        return $this->passport_uid;
    }

    /**
     * Set shopNum
     *
     * @param integer $shopNum
     *
     * @return Resources
     */
    public function setShopNum($shopNum)
    {
        $this->shop_num = $shopNum;

        return $this;
    }

    /**
     * Get shopNum
     *
     * @return integer
     */
    public function getShopNum()
    {
        return $this->shop_num;
    }

    /**
     * Set leftShopNum
     *
     * @param integer $leftShopNum
     *
     * @return Resources
     */
    public function setLeftShopNum($leftShopNum)
    {
        $this->left_shop_num = $leftShopNum;

        return $this;
    }

    /**
     * Get leftShopNum
     *
     * @return integer
     */
    public function getLeftShopNum()
    {
        return $this->left_shop_num;
    }

    /**
     * Set source
     *
     * @param string $source
     *
     * @return Resources
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set availableDays
     *
     * @param integer $availableDays
     *
     * @return Resources
     */
    public function setAvailableDays($availableDays)
    {
        $this->available_days = $availableDays;

        return $this;
    }

    /**
     * Get availableDays
     *
     * @return integer
     */
    public function getAvailableDays()
    {
        return $this->available_days;
    }

    /**
     * Set activeAt
     *
     * @param integer $activeAt
     *
     * @return Resources
     */
    public function setActiveAt($activeAt)
    {
        $this->active_at = $activeAt;

        return $this;
    }

    /**
     * Get activeAt
     *
     * @return integer
     */
    public function getActiveAt()
    {
        return $this->active_at;
    }

    /**
     * Set expiredAt
     *
     * @param integer $expiredAt
     *
     * @return Resources
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expired_at = $expiredAt;

        return $this;
    }

    /**
     * Get expiredAt
     *
     * @return integer
     */
    public function getExpiredAt()
    {
        return $this->expired_at;
    }


    /**
     * Set activeCode
     *
     * @param string $activeCode
     *
     * @return Resources
     */
    public function setActiveCode($activeCode)
    {
        $this->active_code = $activeCode;

        return $this;
    }

    /**
     * Get activeCode
     *
     * @return string
     */
    public function getActiveCode()
    {
        return $this->active_code;
    }

    /**
     * Set issueId
     *
     * @param string $issueId
     *
     * @return Resources
     */
    public function setIssueId($issueId)
    {
        $this->issue_id = $issueId;

        return $this;
    }

    /**
     * Get issueId
     *
     * @return string
     */
    public function getIssueId()
    {
        return $this->issue_id;
    }

    /**
     * Set goodsCode
     *
     * @param string $goodsCode
     *
     * @return Resources
     */
    public function setGoodsCode($goodsCode)
    {
        $this->goods_code = $goodsCode;

        return $this;
    }

    /**
     * Get goodsCode
     *
     * @return string
     */
    public function getGoodsCode()
    {
        return $this->goods_code;
    }

    /**
     * Set productCode
     *
     * @param string $productCode
     *
     * @return Resources
     */
    public function setProductCode($productCode)
    {
        $this->product_code = $productCode;

        return $this;
    }

    /**
     * Get productCode
     *
     * @return string
     */
    public function getProductCode()
    {
        return $this->product_code;
    }
}
