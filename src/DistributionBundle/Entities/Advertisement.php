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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Advertisement 开屏广告
 *
 * @ORM\Table(name="distribution_shopScreen_advertisement", options={"comment":"广告表"})
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\AdvertisementRepository")
 */
class Advertisement
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", options={"comment":"广告id"})
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
     * @var integer
     *
     * @ORM\Column(name="title", type="string", options={"comment":"标题"})
     */
    private $title;

    /**
      * @var integer
      *
      * @ORM\Column(name="thumb_img", type="text", options={"comment":"缩略图"})
      */
    private $thumb_img;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", nullable=true, options={"comment":"排序"})
     */
    private $sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="media_url", type="text", options={"comment":"(图片/视频)地址"})
     */
    private $media_url;

    /**
     * @var integer
     *
     * @ORM\Column(name="media_type", type="string", options={"comment":"类型", "default" : "image"})
     */
    private $media_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="release_time", type="integer", nullable=true, options={"comment":"发布时间"})
     */
    private $release_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="release_status", type="boolean", options={"comment":"发布状态", "default" : false})
     */
    private $release_status = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"作者id"})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"分销商id", "default": 0})
     */
    private $distributor_id;

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
     * Get articleId
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
     * @return Article
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
     * Set title
     *
     * @param string $title
     *
     * @return Article
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
     * Set thumbImg
     *
     * @param string $thumbImg
     *
     * @return Advertisement
     */
    public function setThumbImg($thumbImg)
    {
        $this->thumb_img = $thumbImg;

        return $this;
    }

    /**
     * Get thumbImg
     *
     * @return string
     */
    public function getThumbImg()
    {
        return $this->thumb_img;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return Article
     */
    public function setMediaUrl($mediaUrl)
    {
        $this->media_url = $mediaUrl;

        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->media_url;
    }

    /**
     * Set mediaType
     *
     * @param string $mediaType
     *
     * @return Article
     */
    public function setMediaType($mediaType)
    {
        $this->media_type = $mediaType;

        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string
     */
    public function getMediaType()
    {
        return $this->media_type;
    }

    /**
     * Set releaseTime
     *
     * @param integer $releaseTime
     *
     * @return Article
     */
    public function setReleaseTime($releaseTime)
    {
        $this->release_time = $releaseTime;

        return $this;
    }

    /**
     * Get releaseTime
     *
     * @return integer
     */
    public function getReleaseTime()
    {
        return $this->release_time;
    }

    /**
     * Set releaseStatus
     *
     * @param boolean $releaseStatus
     *
     * @return Article
     */
    public function setReleaseStatus($releaseStatus)
    {
        $this->release_status = $releaseStatus;

        return $this;
    }

    /**
     * Get releaseStatus
     *
     * @return boolean
     */
    public function getReleaseStatus()
    {
        return $this->release_status;
    }

    /**
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return Article
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Article
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
     * @return Article
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
     * Set sort
     *
     * @param integer $sort
     *
     * @return Article
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
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
     * Get operatorId.
     *
     * @return int|null
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }
}
