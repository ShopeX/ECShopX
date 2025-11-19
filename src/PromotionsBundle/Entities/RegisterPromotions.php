<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * RegisterPromotions 注册营销表
 *
 * @ORM\Table(name="register_promotions", options={"comment":"注册营销表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\RegisterPromotionsRepository")
 */
class RegisterPromotions
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"注册促销活动ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="is_open", type="string", options={"comment":"是否开启"})
     */
    private $is_open;

    /**
     * @var string
     *
     * @ORM\Column(name="register_type", type="string", options={"comment":"促销类型。可选值有 general-普通;distributor-分销商", "default":"general"})
     */
    private $register_type = "general";

    /**
     * @var string
     *
     * @ORM\Column(name="ad_title", type="string", nullable=true, options={"comment":"注册引导广告标题"})
     */
    private $ad_title;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_pic", type="string", options={"comment":"注册引导图片"})
     */
    private $ad_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="register_jump_path", type="string", length=500, nullable=true, options={"comment":"注册引导跳转路径"})
     */
    private $register_jump_path;

    /**
     * @var string
     *
     * @ORM\Column(name="promotions_value", type="text", options={"comment":"赠送的参数"})
     */
    private $promotions_value;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RegisterPromotions
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
     * Set adTitle
     *
     * @param string $adTitle
     *
     * @return RegisterPromotions
     */
    public function setAdTitle($adTitle)
    {
        $this->ad_title = $adTitle;

        return $this;
    }

    /**
     * Get adTitle
     *
     * @return string
     */
    public function getAdTitle()
    {
        return $this->ad_title;
    }

    /**
     * Set adPic
     *
     * @param string $adPic
     *
     * @return RegisterPromotions
     */
    public function setAdPic($adPic)
    {
        $this->ad_pic = $adPic;

        return $this;
    }

    /**
     * Get adPic
     *
     * @return string
     */
    public function getAdPic()
    {
        return $this->ad_pic;
    }

    /**
     * Set promotionsValue
     *
     * @param string $promotionsValue
     *
     * @return RegisterPromotions
     */
    public function setPromotionsValue($promotionsValue)
    {
        $this->promotions_value = $promotionsValue;

        return $this;
    }

    /**
     * Get promotionsValue
     *
     * @return string
     */
    public function getPromotionsValue()
    {
        return $this->promotions_value;
    }

    /**
     * Set isOpen
     *
     * @param string $isOpen
     *
     * @return RegisterPromotions
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get isOpen
     *
     * @return string
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set registerType
     *
     * @param string $registerType
     *
     * @return RegisterPromotions
     */
    public function setRegisterType($registerType)
    {
        $this->register_type = $registerType;

        return $this;
    }

    /**
     * Get registerType
     *
     * @return string
     */
    public function getRegisterType()
    {
        return $this->register_type;
    }

    /**
     * Set registerJumpPath.
     *
     * @param string|null $registerJumpPath
     *
     * @return RegisterPromotions
     */
    public function setRegisterJumpPath($registerJumpPath = null)
    {
        $this->register_jump_path = $registerJumpPath;

        return $this;
    }

    /**
     * Get registerJumpPath.
     *
     * @return string|null
     */
    public function getRegisterJumpPath()
    {
        return $this->register_jump_path;
    }
}
