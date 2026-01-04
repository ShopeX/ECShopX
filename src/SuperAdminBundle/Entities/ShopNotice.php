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

namespace SuperAdminBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ShopNotice 店铺公告
 *
 * @ORM\Table(name="shop_notice",options={"comment"="店铺公告表"})
 * @ORM\Entity(repositoryClass="SuperAdminBundle\Repositories\ShopNoticeRepository")
 */
class ShopNotice
{
    /**
     * @var integer
     *
     * @ORM\Column(name="notice_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $notice_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", options={"comment":"公告类型。可选值有 notice-公告;helper-店主助手"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", options={"comment":"公告标题"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="web_link", type="string", options={"comment":"网页链接"})
     */
    private $web_link;

    /**
     * @var string
     *
     * @ORM\Column(name="is_publish", type="boolean", options={"comment":"是否发布 0:不发布 1:发布"})
     */
    private $is_publish = 0;

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
     * Get noticeId
     *
     * @return integer
     */
    public function getNoticeId()
    {
        return $this->notice_id;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return ShopNotice
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return ShopNotice
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
     * Set webLink
     *
     * @param string $webLink
     *
     * @return ShopNotice
     */
    public function setWebLink($webLink)
    {
        $this->web_link = $webLink;

        return $this;
    }

    /**
     * Get webLink
     *
     * @return string
     */
    public function getWebLink()
    {
        return $this->web_link;
    }

    /**
     * Set isPublish
     *
     * @param boolean $isPublish
     *
     * @return ShopNotice
     */
    public function setIsPublish($isPublish)
    {
        $this->is_publish = $isPublish;

        return $this;
    }

    /**
     * Get isPublish
     *
     * @return boolean
     */
    public function getIsPublish()
    {
        return $this->is_publish;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ShopNotice
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
     * @return ShopNotice
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
