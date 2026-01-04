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
 * theme_member_center_share 会员中心分享信息表
 *
 * @ORM\Table(name="theme_member_center_share", options={"comment":"会员中心分享信息表"},
 * indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\ThemeMemberCenterShareRepository")
 */
class ThemeMemberCenterShare
{
    /**
     * @var integer
     *
     * @ORM\Column(name="theme_member_center_share_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $theme_pc_template_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="share_title", type="string", length=50, options={"comment":"页面名称"})
     */
    private $share_title;

    /**
     * @var string
     *
     * @ORM\Column(name="share_description", type="string", length=150, options={"comment":"页面描述"})
     */
    private $share_description;

    /**
     * @var string
     *
     * @ORM\Column(name="share_pic_wechatapp", nullable=true, type="string", length=150, options={"comment":"分享图片小程序", "default": ""})
     */
    private $share_pic_wechatapp;

    /**
     * @var integer
     *
     * @ORM\Column(name="share_pic_h5", type="string", nullable=true, length=150, options={"comment":"分享图片h5", "default":""})
     */
    private $share_pic_h5;

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
     * Get themePcTemplateId.
     *
     * @return int
     */
    public function getThemePcTemplateId()
    {
        return $this->theme_pc_template_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ThemeMemberCenterShare
     */
    public function setCompanyId($companyId)
    {
        // 53686f704578
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
     * Set shareTitle.
     *
     * @param string $shareTitle
     *
     * @return ThemeMemberCenterShare
     */
    public function setShareTitle($shareTitle)
    {
        $this->share_title = $shareTitle;

        return $this;
    }

    /**
     * Get shareTitle.
     *
     * @return string
     */
    public function getShareTitle()
    {
        // 53686f704578
        return $this->share_title;
    }

    /**
     * Set shareDescription.
     *
     * @param string $shareDescription
     *
     * @return ThemeMemberCenterShare
     */
    public function setShareDescription($shareDescription)
    {
        $this->share_description = $shareDescription;

        return $this;
    }

    /**
     * Get shareDescription.
     *
     * @return string
     */
    public function getShareDescription()
    {
        return $this->share_description;
    }

    /**
     * Set sharePicWechatapp.
     *
     * @param string|null $sharePicWechatapp
     *
     * @return ThemeMemberCenterShare
     */
    public function setSharePicWechatapp($sharePicWechatapp = null)
    {
        $this->share_pic_wechatapp = $sharePicWechatapp;

        return $this;
    }

    /**
     * Get sharePicWechatapp.
     *
     * @return string|null
     */
    public function getSharePicWechatapp()
    {
        return $this->share_pic_wechatapp;
    }

    /**
     * Set sharePicH5.
     *
     * @param string|null $sharePicH5
     *
     * @return ThemeMemberCenterShare
     */
    public function setSharePicH5($sharePicH5 = null)
    {
        $this->share_pic_h5 = $sharePicH5;

        return $this;
    }

    /**
     * Get sharePicH5.
     *
     * @return string|null
     */
    public function getSharePicH5()
    {
        return $this->share_pic_h5;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ThemeMemberCenterShare
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
     * @return ThemeMemberCenterShare
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
}
