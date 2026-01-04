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

namespace ThemeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Set  开屏广告设置
 *
 * @ORM\Table(name="pages_open_screen_ad", options={"comment":"开屏广告设置"}, indexes={
 *    @ORM\Index(name="ix_id", columns={"id"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\OpenScreenAdRepository")
 */
class OpenScreenAd
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", options={"comment":"设置id"})
     * @ORM\Id
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
     * @ORM\Column(name="ad_material", type="string",  nullable=false, options={"comment":"广告素材"})
     */
    private $ad_material;


    /**
     * @var string
     *
     * @ORM\Column(name="is_enable", type="integer", options={"comment":"是否启用0否,1是","default": 0})
     */
    private $is_enable = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="show_time", type="string", options={"comment":"曝光设置：first,always","default": "first"})
     */
    private $show_time = 'first';

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", options={"comment":"倒计时位置：right_top,right_bottom","default": 0})
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="is_jump", type="integer", options={"comment":"是否跳过0否,1是","default": 0})
     */
    private $is_jump = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="material_type", type="integer", options={"comment":"素材类型1图片,2视频","default": 1})
     */
    private $material_type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="waiting_time", type="integer", options={"comment":"等待时间，秒","default": 0})
     */
    private $waiting_time = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_url", type="string", length=1000, nullable=false, options={"comment":"广告链接"})
     */
    private $ad_url;

    /**
     * @var string
     *
     * @ORM\Column(name="app", type="string",length=100, nullable=false, options={"comment":"设置应用, all 全部,app APP,wapp 小程序 ", "default": 0})
     */
    private $app;

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
     * @return OpenScreenAd
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
     * Set adMaterial.
     *
     * @param string $adMaterial
     *
     * @return OpenScreenAd
     */
    public function setAdMaterial($adMaterial)
    {
        $this->ad_material = $adMaterial;

        return $this;
    }

    /**
     * Get adMaterial.
     *
     * @return string
     */
    public function getAdMaterial()
    {
        return $this->ad_material;
    }

    /**
     * Set isEnable.
     *
     * @param int $isEnable
     *
     * @return OpenScreenAd
     */
    public function setIsEnable($isEnable)
    {
        $this->is_enable = $isEnable;

        return $this;
    }

    /**
     * Get isEnable.
     *
     * @return int
     */
    public function getIsEnable()
    {
        return $this->is_enable;
    }

    /**
     * Set showTime.
     *
     * @param int $showTime
     *
     * @return OpenScreenAd
     */
    public function setShowTime($showTime)
    {
        $this->show_time = $showTime;

        return $this;
    }

    /**
     * Get showTime.
     *
     * @return int
     */
    public function getShowTime()
    {
        return $this->show_time;
    }

    /**
     * Set waitingTime.
     *
     * @param int $waitingTime
     *
     * @return OpenScreenAd
     */
    public function setWaitingTime($waitingTime)
    {
        $this->waiting_time = $waitingTime;

        return $this;
    }

    /**
     * Get waitingTime.
     *
     * @return int
     */
    public function getWaitingTime()
    {
        return $this->waiting_time;
    }

    /**
     * Set adUrl.
     *
     * @param string $adUrl
     *
     * @return OpenScreenAd
     */
    public function setAdUrl($adUrl)
    {
        $this->ad_url = $adUrl;

        return $this;
    }

    /**
     * Get adUrl.
     *
     * @return string
     */
    public function getAdUrl()
    {
        return $this->ad_url;
    }

    /**
     * Set app.
     *
     * @param string $app
     *
     * @return OpenScreenAd
     */
    public function setApp($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get app.
     *
     * @return string
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OpenScreenAd
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
     * @param int|null $updated
     *
     * @return OpenScreenAd
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set materialType.
     *
     * @param int $materialType
     *
     * @return OpenScreenAd
     */
    public function setMaterialType($materialType)
    {
        $this->material_type = $materialType;

        return $this;
    }

    /**
     * Get materialType.
     *
     * @return int
     */
    public function getMaterialType()
    {
        return $this->material_type;
    }

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return OpenScreenAd
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set isJump.
     *
     * @param int $isJump
     *
     * @return OpenScreenAd
     */
    public function setIsJump($isJump)
    {
        $this->is_jump = $isJump;

        return $this;
    }

    /**
     * Get isJump.
     *
     * @return int
     */
    public function getIsJump()
    {
        return $this->is_jump;
    }
}
