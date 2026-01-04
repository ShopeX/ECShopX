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

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * VipGrade(付费会员等级库表)
 *
 * @ORM\Table(name="kaquan_vip_grade", options={"comment":"付费会员等级库表"}, indexes={
 *    @ORM\Index(name="idx_companyid_created", columns={"company_id","created"})
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\VipGradeRepository")
 */
class VipGrade
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="vip_grade_id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $vip_grade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="integer", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="grade_name", type="string", options={"comment":"等级名称"})
     */
    private $grade_name;

    /**
     * @var string
     *
     * @ORM\Column(name="lv_type", type="string", options={"comment":"等级类型,可选值有 vip:普通vip;svip:进阶vip"})
     */
    private $lv_type = 'vip';

    /**
     * @var string
     *
     * @ORM\Column(name="guide_title", type="string", nullable=true, options={"comment":"购买引导文本"})
     */
    private $guide_title;

    /**
     * @var string
     *
     * @ORM\Column(name="is_default", type="boolean", options={"comment":"购买引导文本", "default": false})
     */
    private $is_default = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="default_grade", nullable=true, type="boolean", options={"comment":"是否默认等级", "default":false})
     */
    private $default_grade = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_disabled", nullable=true, type="boolean", options={"comment":"是否禁用", "default":false})
     */
    private $is_disabled = false;

    /**
     * @var json_array
     *
     * @ORM\Column(name="background_pic_url", nullable=true, type="string", length=1024, options={"comment":"商家自定义会员卡背景图"})
     */
    private $background_pic_url;

    /**
     * @var string
     *
     * @ORM\Column(name="price_list", nullable=true, type="text", options={"comment":"阶段价格表"})
     */
    private $price_list;

    /**
     * @var string
     *
     * @ORM\Column(name="privileges", nullable=true, type="text", options={"comment":"会员权益"})
     */
    private $privileges;

    /**
     * @var string
     *
     * @ORM\Column(name="description", nullable=true, type="text", options={"comment":"详细说明"})
     */
    private $description;

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
     * @ORM\Column(type="integer")
     */
    protected $updated;

    /**
     * @var string
     *
     * @ORM\Column(name="external_id", nullable=false, type="string", length=50, options={"comment":"外部唯一标识，外部调用方自定义的值", "default":""})
     */
    private $external_id;

    /**
     * Get vipGradeId
     *
     * @return integer
     */
    public function getVipGradeId()
    {
        return $this->vip_grade_id;
    }

    /**
     * Set companyId
     *
     * @param string $companyId
     *
     * @return VipGrade
     */
    public function setCompanyId($companyId)
    {
        // Ver: 8d1abe8e
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set gradeName
     *
     * @param string $gradeName
     *
     * @return VipGrade
     */
    public function setGradeName($gradeName)
    {
        $this->grade_name = $gradeName;

        return $this;
    }

    /**
     * Get gradeName
     *
     * @return string
     */
    public function getGradeName()
    {
        // Ver: 8d1abe8e
        return $this->grade_name;
    }

    /**
     * Set defaultGrade
     *
     * @param boolean $defaultGrade
     *
     * @return VipGrade
     */
    public function setDefaultGrade($defaultGrade)
    {
        $this->default_grade = $defaultGrade;

        return $this;
    }

    /**
     * Get defaultGrade
     *
     * @return boolean
     */
    public function getDefaultGrade()
    {
        return $this->default_grade;
    }

    /**
     * Set backgroundPicUrl
     *
     * @param string $backgroundPicUrl
     *
     * @return VipGrade
     */
    public function setBackgroundPicUrl($backgroundPicUrl)
    {
        $this->background_pic_url = $backgroundPicUrl;

        return $this;
    }

    /**
     * Get backgroundPicUrl
     *
     * @return string
     */
    public function getBackgroundPicUrl()
    {
        return $this->background_pic_url;
    }

    /**
     * Set priceList
     *
     * @param array $priceList
     *
     * @return VipGrade
     */
    public function setPriceList($priceList)
    {
        $this->price_list = $priceList;

        return $this;
    }

    /**
     * Get priceList
     *
     * @return array
     */
    public function getPriceList()
    {
        return $this->price_list;
    }

    /**
     * Set privileges
     *
     * @param array $privileges
     *
     * @return VipGrade
     */
    public function setPrivileges($privileges)
    {
        $this->privileges = $privileges;

        return $this;
    }

    /**
     * Get privileges
     *
     * @return array
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return VipGrade
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
     * @return VipGrade
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
     * Set lvType
     *
     * @param string $lvType
     *
     * @return VipGrade
     */
    public function setLvType($lvType)
    {
        $this->lv_type = $lvType;

        return $this;
    }

    /**
     * Get lvType
     *
     * @return string
     */
    public function getLvType()
    {
        return $this->lv_type;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return VipGrade
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isDisabled
     *
     * @param boolean $isDisabled
     *
     * @return VipGrade
     */
    public function setIsDisabled($isDisabled)
    {
        $this->is_disabled = $isDisabled;

        return $this;
    }

    /**
     * Get isDisabled
     *
     * @return boolean
     */
    public function getIsDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * Set guideTitle
     *
     * @param string $guideTitle
     *
     * @return VipGrade
     */
    public function setGuideTitle($guideTitle)
    {
        $this->guide_title = $guideTitle;

        return $this;
    }

    /**
     * Get guideTitle
     *
     * @return string
     */
    public function getGuideTitle()
    {
        return $this->guide_title;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     *
     * @return VipGrade
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set externalId.
     *
     * @param string $externalId
     *
     * @return VipGrade
     */
    public function setExternalId($externalId)
    {
        $this->external_id = $externalId;

        return $this;
    }

    /**
     * Get externalId.
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->external_id;
    }
}
