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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AdapayMerchantResident adapay商户入驻
 *
 * @ORM\Table(name="adapay_merchant_resident", options={"comment":"adapay商户入驻"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_request_id", columns={"request_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayMerchantResidentRepository")
 */
class AdapayMerchantResident
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
     * @var string
     *
     * @ORM\Column(name="request_id", type="string", length=100, options={"comment":"请求ID"})
     */
    private $request_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_api_key", type="string", options={"comment":"商户开户进件返回的API Key"})
     */
    private $sub_api_key;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_type", type="string", length=10, options={"comment":"费率类型：01-标准费率线上，02-标准费率线下"})
     */
    private $fee_type;

    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", options={"comment":"商户开户进件返回的应用ID"})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="wx_category", type="string", length=50, nullable=true, options={"comment":"微信经营类目（与支付宝二选一）"})
     */
    private $wx_category;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_category", type="string", length=50, nullable=true, options={"comment":"支付宝经营类目（与微信二选一）"})
     */
    private $alipay_category;

    /**
     * @var string
     *
     * @ORM\Column(name="cls_id", type="string", length=50, nullable=true, options={"comment":"行业分类"})
     */
    private $cls_id;

    /**
     * @var string
     *
     * @ORM\Column(name="model_type", type="string", length=10, options={"comment":"入驻模式：1-服务商模式"})
     */
    private $model_type;

    /**
     * @var string
     *
     * @ORM\Column(name="mer_type", type="string", length=10, options={"comment":"商户种类，1-政府机构,2-国营企业,3-私营企业,4-外资企业,5-个体工商户,7-事业单位,8-小微"})
     */
    private $mer_type;

    /**
     * @var string
     *
     * @ORM\Column(name="province_code", type="string", length=20, options={"comment":"省份编码"})
     */
    private $province_code;

    /**
     * @var string
     *
     * @ORM\Column(name="city_code", type="string", length=20, options={"comment":"城市编码"})
     */
    private $city_code;

    /**
     * @var string
     *
     * @ORM\Column(name="district_code", type="string", length=20, options={"comment":"区县编码"})
     */
    private $district_code;

    /**
     * @var string
     *
     * @ORM\Column(name="add_value_list", type="text", options={"comment":"支付渠道配置信息"})
     */
    private $add_value_list;

    /**
     * @var string
     *
     * @ORM\Column(name="adapay_fee_mode", type="string", length=20, options={"comment":"总商户 手续费扣除方式 I:内扣 O:外扣"})
     */
    private $adapay_fee_mode;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, options={"comment":"接口调用状态，succeeded - 成功 failed - 失败 pending - 处理中"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_stat", type="string", length=20, nullable=true, options={"comment":"支付宝入驻结果：S-成功，F-失败"})
     */
    private $alipay_stat;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_stat_msg", type="string", length=500, nullable=true, options={"comment":"支付宝入驻错误描述"})
     */
    private $alipay_stat_msg;

    /**
     * @var string
     *
     * @ORM\Column(name="wx_stat", type="string", length=20, nullable=true, options={"comment":"微信入驻结果：S-成功，F-失败"})
     */
    private $wx_stat;

    /**
     * @var string
     *
     * @ORM\Column(name="wx_stat_msg", type="string", length=500, nullable=true, options={"comment":"微信入驻错误描述"})
     */
    private $wx_stat_msg;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;

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
     * @return AdapayMerchantResident
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
     * Set requestId.
     *
     * @param string $requestId
     *
     * @return AdapayMerchantResident
     */
    public function setRequestId($requestId)
    {
        $this->request_id = $requestId;

        return $this;
    }

    /**
     * Get requestId.
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->request_id;
    }

    /**
     * Set subApiKey.
     *
     * @param string $subApiKey
     *
     * @return AdapayMerchantResident
     */
    public function setSubApiKey($subApiKey)
    {
        $this->sub_api_key = $subApiKey;

        return $this;
    }

    /**
     * Get subApiKey.
     *
     * @return string
     */
    public function getSubApiKey()
    {
        return $this->sub_api_key;
    }

    /**
     * Set feeType.
     *
     * @param string $feeType
     *
     * @return AdapayMerchantResident
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
     * Set appId.
     *
     * @param string $appId
     *
     * @return AdapayMerchantResident
     */
    public function setAppId($appId)
    {
        $this->app_id = $appId;

        return $this;
    }

    /**
     * Get appId.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * Set wxCategory.
     *
     * @param string|null $wxCategory
     *
     * @return AdapayMerchantResident
     */
    public function setWxCategory($wxCategory = null)
    {
        $this->wx_category = $wxCategory;

        return $this;
    }

    /**
     * Get wxCategory.
     *
     * @return string|null
     */
    public function getWxCategory()
    {
        return $this->wx_category;
    }

    /**
     * Set alipayCategory.
     *
     * @param string|null $alipayCategory
     *
     * @return AdapayMerchantResident
     */
    public function setAlipayCategory($alipayCategory = null)
    {
        $this->alipay_category = $alipayCategory;

        return $this;
    }

    /**
     * Get alipayCategory.
     *
     * @return string|null
     */
    public function getAlipayCategory()
    {
        return $this->alipay_category;
    }

    /**
     * Set clsId.
     *
     * @param string|null $clsId
     *
     * @return AdapayMerchantResident
     */
    public function setClsId($clsId = null)
    {
        $this->cls_id = $clsId;

        return $this;
    }

    /**
     * Get clsId.
     *
     * @return string|null
     */
    public function getClsId()
    {
        return $this->cls_id;
    }

    /**
     * Set modelType.
     *
     * @param string $modelType
     *
     * @return AdapayMerchantResident
     */
    public function setModelType($modelType)
    {
        $this->model_type = $modelType;

        return $this;
    }

    /**
     * Get modelType.
     *
     * @return string
     */
    public function getModelType()
    {
        return $this->model_type;
    }

    /**
     * Set merType.
     *
     * @param string $merType
     *
     * @return AdapayMerchantResident
     */
    public function setMerType($merType)
    {
        $this->mer_type = $merType;

        return $this;
    }

    /**
     * Get merType.
     *
     * @return string
     */
    public function getMerType()
    {
        return $this->mer_type;
    }

    /**
     * Set provinceCode.
     *
     * @param string $provinceCode
     *
     * @return AdapayMerchantResident
     */
    public function setProvinceCode($provinceCode)
    {
        $this->province_code = $provinceCode;

        return $this;
    }

    /**
     * Get provinceCode.
     *
     * @return string
     */
    public function getProvinceCode()
    {
        return $this->province_code;
    }

    /**
     * Set cityCode.
     *
     * @param string $cityCode
     *
     * @return AdapayMerchantResident
     */
    public function setCityCode($cityCode)
    {
        $this->city_code = $cityCode;

        return $this;
    }

    /**
     * Get cityCode.
     *
     * @return string
     */
    public function getCityCode()
    {
        return $this->city_code;
    }

    /**
     * Set districtCode.
     *
     * @param string $districtCode
     *
     * @return AdapayMerchantResident
     */
    public function setDistrictCode($districtCode)
    {
        $this->district_code = $districtCode;

        return $this;
    }

    /**
     * Get districtCode.
     *
     * @return string
     */
    public function getDistrictCode()
    {
        return $this->district_code;
    }

    /**
     * Set addValueList.
     *
     * @param string $addValueList
     *
     * @return AdapayMerchantResident
     */
    public function setAddValueList($addValueList)
    {
        $this->add_value_list = $addValueList;

        return $this;
    }

    /**
     * Get addValueList.
     *
     * @return string
     */
    public function getAddValueList()
    {
        return $this->add_value_list;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return AdapayMerchantResident
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set alipayStat.
     *
     * @param string|null $alipayStat
     *
     * @return AdapayMerchantResident
     */
    public function setAlipayStat($alipayStat = null)
    {
        $this->alipay_stat = $alipayStat;

        return $this;
    }

    /**
     * Get alipayStat.
     *
     * @return string|null
     */
    public function getAlipayStat()
    {
        return $this->alipay_stat;
    }

    /**
     * Set alipayStatMsg.
     *
     * @param string|null $alipayStatMsg
     *
     * @return AdapayMerchantResident
     */
    public function setAlipayStatMsg($alipayStatMsg = null)
    {
        $this->alipay_stat_msg = $alipayStatMsg;

        return $this;
    }

    /**
     * Get alipayStatMsg.
     *
     * @return string|null
     */
    public function getAlipayStatMsg()
    {
        return $this->alipay_stat_msg;
    }

    /**
     * Set wxStat.
     *
     * @param string|null $wxStat
     *
     * @return AdapayMerchantResident
     */
    public function setWxStat($wxStat = null)
    {
        $this->wx_stat = $wxStat;

        return $this;
    }

    /**
     * Get wxStat.
     *
     * @return string|null
     */
    public function getWxStat()
    {
        return $this->wx_stat;
    }

    /**
     * Set wxStatMsg.
     *
     * @param string|null $wxStatMsg
     *
     * @return AdapayMerchantResident
     */
    public function setWxStatMsg($wxStatMsg = null)
    {
        $this->wx_stat_msg = $wxStatMsg;

        return $this;
    }

    /**
     * Get wxStatMsg.
     *
     * @return string|null
     */
    public function getWxStatMsg()
    {
        return $this->wx_stat_msg;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayMerchantResident
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime.
     *
     * @return int
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime.
     *
     * @param int|null $updateTime
     *
     * @return AdapayMerchantResident
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime.
     *
     * @return int|null
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set adapayFeeMode.
     *
     * @param string $adapayFeeMode
     *
     * @return AdapayMerchantResident
     */
    public function setAdapayFeeMode($adapayFeeMode)
    {
        $this->adapay_fee_mode = $adapayFeeMode;

        return $this;
    }

    /**
     * Get adapayFeeMode.
     *
     * @return string
     */
    public function getAdapayFeeMode()
    {
        return $this->adapay_fee_mode;
    }
}
