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

namespace AdaPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * AdapayWxBusinessCategory 微信经营类目
 *
 * @ORM\Table(name="adapay_wx_business_category", options={"comment":"微信经营类目"},
 *     indexes={
 *         @ORM\Index(name="idx_merchant_type_name", columns={"merchant_type_name"}),
 *         @ORM\Index(name="idx_business_category_id", columns={"business_category_id"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayWxBusinessCategoryRepository")
 */
class AdapayWxBusinessCategory
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
     * @var string
     *
     * @ORM\Column(name="fee_type", type="string", length=10, options={"comment":"费率类型"})
     */
    private $fee_type;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_type_name", type="string", length=50, options={"comment":"费率类型名称"})
     */
    private $fee_type_name;

    /**
     * @var string
     *
     * @ORM\Column(name="merchant_type_name", type="string", length=50, options={"comment":"商户种类名称"})
     */
    private $merchant_type_name;

    /**
     * @var string
     *
     * @ORM\Column(name="business_category_id", type="string", length=50, options={"comment":"微信经营类目id"})
     */
    private $business_category_id;

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
     * Set feeType.
     *
     * @param string $feeType
     *
     * @return AdapayWxBusinessCategory
     */
    public function setFeeType($feeType)
    {
        $this->fee_type = $feeType;

        return $this;
    }

    /**
     * Get feeType.
     *
     * @return string
     */
    public function getFeeType()
    {
        return $this->fee_type;
    }

    /**
     * Set feeTypeName.
     *
     * @param string $feeTypeName
     *
     * @return AdapayWxBusinessCategory
     */
    public function setFeeTypeName($feeTypeName)
    {
        $this->fee_type_name = $feeTypeName;

        return $this;
    }

    /**
     * Get feeTypeName.
     *
     * @return string
     */
    public function getFeeTypeName()
    {
        return $this->fee_type_name;
    }

    /**
     * Set merchantTypeName.
     *
     * @param string $merchantTypeName
     *
     * @return AdapayWxBusinessCategory
     */
    public function setMerchantTypeName($merchantTypeName)
    {
        $this->merchant_type_name = $merchantTypeName;

        return $this;
    }

    /**
     * Get merchantTypeName.
     *
     * @return string
     */
    public function getMerchantTypeName()
    {
        return $this->merchant_type_name;
    }

    /**
     * Set businessCategoryId.
     *
     * @param string $businessCategoryId
     *
     * @return AdapayWxBusinessCategory
     */
    public function setBusinessCategoryId($businessCategoryId)
    {
        $this->business_category_id = $businessCategoryId;

        return $this;
    }

    /**
     * Get businessCategoryId.
     *
     * @return string
     */
    public function getBusinessCategoryId()
    {
        return $this->business_category_id;
    }
}
