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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Distributor
 *
 * @ORM\Table(name="distribution_shopScreen_slider")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\SliderRepository")
 */
class Slider
{
    /**
     * @var integer
     *
     * @ORM\Column(name="slide_id", type="bigint", options={"comment":"轮播id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $slide_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"分销商id", "default": 0})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="title", type="string", options={"comment":"标题"})
     */
    private $title;

    /**
    * @var integer
    *
    * @ORM\Column(name="sub_title", type="string", options={"comment":"副标题"})
    */
    private $sub_title;

    /**
     * @var array
     *
     * @ORM\Column(name="style_params", type="array", options={"comment":"风格参数"})
     */
    private $style_params;

    /**
     * @var integer
     *
     * @ORM\Column(name="desc_status", type="boolean", options={"comment":"图片描述状态11", "default" : false})
     */
    private $desc_status = false;

    /**
     * @var array
     *
     * @ORM\Column(name="image_list", type="array", options={"comment":"轮播图列表"})
     */
    private $image_list;


    /**
     * Set slideId.
     *
     * @param int $slideId
     *
     * @return Slider
     */
    public function setSlideId($slideId)
    {
        $this->slide_id = $slideId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getSlideId()
    {
        return $this->slide_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Slider
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return Article
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Slider
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set subTitle.
     *
     * @param string $subTitle
     *
     * @return Slider
     */
    public function setSubTitle($subTitle)
    {
        $this->sub_title = $subTitle;

        return $this;
    }

    /**
     * Get subTitle.
     *
     * @return string
     */
    public function getSubTitle()
    {
        return $this->sub_title;
    }

    /**
     * Set styleParams.
     *
     * @param array $styleParams
     *
     * @return Slider
     */
    public function setStyleParams($styleParams)
    {
        $this->style_params = $styleParams;

        return $this;
    }

    /**
     * Get styleParams.
     *
     * @return array
     */
    public function getStyleParams()
    {
        return $this->style_params;
    }

    /**
     * Set desc_status.
     *
     * @return array
     */
    public function setDescStatus($descStatus)
    {
        $this->desc_status = $descStatus;
        return $this;
    }
    /**
     * Get desc_status.
     *
     * @return array
     */
    public function getDescStatus()
    {
        return $this->desc_status;
    }

    /**
     * Set imageList.
     *
     * @param array $imageList
     *
     * @return Slider
     */
    public function setImageList($imageList)
    {
        $this->image_list = $imageList;

        return $this;
    }

    /**
     * Get imageList.
     *
     * @return array
     */
    public function getImageList()
    {
        return $this->image_list;
    }
}
