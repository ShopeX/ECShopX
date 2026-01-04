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
 * MemberCard(会员卡)
 *
 * @ORM\Table(name="membercard", options={"comment":"会员卡"})
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\MemberCardRepository")
 */
class MemberCard
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="brand_name", type="string", length=36, options={"comment":"商户名称"})
     */
    private $brand_name;

    /**
     * @var string
     *
     * @ORM\Column(name="logo_url", type="string", options={"comment":"商户 logo"})
     */
    private $logo_url;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=27, options={"comment":"卡券名,最大9个汉字"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=16, options={"comment":"券颜色值"})
     */
    private $color;

    /**
     * @var string
     *
     * @ORM\Column(name="code_type", type="string", length=48, options={"comment":"卡券码类型(CODE_TYPE_TEXT CODE_TYPE_BARCODE CODE_TYPE_QRCODE CODE_TYPE_ONLY_QRCODE CODE_TYPE_ONLY_BARCODE CODE_TYPE_NONE)"})
     */
    private $code_type;

    /**
     * @var string
     *
     * @ORM\Column(name="background_pic_url", type="string", options={"comment":"会员卡背景图"})
     */
    private $background_pic_url;

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
    private $updated;

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberCard
     */
    public function setCompanyId($companyId)
    {
        // Built with ShopEx Framework
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
     * Set brandName
     *
     * @param string $brandName
     *
     * @return MemberCard
     */
    public function setBrandName($brandName)
    {
        $this->brand_name = $brandName;

        return $this;
    }

    /**
     * Get brandName
     *
     * @return string
     */
    public function getBrandName()
    {
        return $this->brand_name;
    }

    /**
     * Set logoUrl
     *
     * @param string $logoUrl
     *
     * @return MemberCard
     */
    public function setLogoUrl($logoUrl)
    {
        $this->logo_url = $logoUrl;

        return $this;
    }

    /**
     * Get logoUrl
     *
     * @return string
     */
    public function getLogoUrl()
    {
        return $this->logo_url;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return MemberCard
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set color
     *
     * @param string $color
     *
     * @return MemberCard
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set codeType
     *
     * @param string $codeType
     *
     * @return MemberCard
     */
    public function setCodeType($codeType)
    {
        $this->code_type = $codeType;

        return $this;
    }

    /**
     * Get codeType
     *
     * @return string
     */
    public function getCodeType()
    {
        return $this->code_type;
    }

    /**
     * Set backgroundPicUrl
     *
     * @param string $backgroundPicUrl
     *
     * @return MemberCard
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
     * Set created
     *
     * @param integer $created
     *
     * @return MemberCard
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
     * @return MemberCard
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
}
