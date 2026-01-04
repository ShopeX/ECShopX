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
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * pages_template_set 页面模板设置表
 *
 * @ORM\Table(name="pages_template_set", options={"comment":"模板设置表"},
 * indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\PagesTemplateSetRepository")
 */
class PagesTemplateSet
{
    use Timestamps;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="index_type", nullable=true, type="integer", options={"comment":"首页类型 1总部首页 2店铺首页", "default":1})
     */
    private $index_type = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="pages_template_id", nullable=true, type="bigint", options={"comment":"关联模版ID", "default":0})
     */
    private $pages_template_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="is_enforce_sync", nullable=true, type="integer", options={"comment":"店铺首页同步状态 1强制同步 2非强制同步", "default":2})
     */
    private $is_enforce_sync = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="is_open_recommend", nullable=true, type="integer", options={"comment":"开启猜你喜欢 1开启 2关闭", "default":2})
     */
    private $is_open_recommend = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="is_open_wechatapp_location", nullable=true, type="integer", options={"comment":"开启小程序定位 1开启 2关闭", "default":1})
     */
    private $is_open_wechatapp_location = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="is_open_scan_qrcode", nullable=true, type="integer", options={"comment":"开启扫码功能 1开启 2关闭", "default":2})
     */
    private $is_open_scan_qrcode = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="is_open_official_account", nullable=true, type="integer", options={"comment":"开启关注公众号组件 1开启 2关闭", "default":2})
     */
    private $is_open_official_account = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="tab_bar", nullable=true, type="text", options={"comment":"小程序菜单设置"})
     */
    private $tab_bar;

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
     * @return PagesTemplateSet
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
     * Set indexType.
     *
     * @param int|null $indexType
     *
     * @return PagesTemplateSet
     */
    public function setIndexType($indexType = null)
    {
        $this->index_type = $indexType;

        return $this;
    }

    /**
     * Get indexType.
     *
     * @return int|null
     */
    public function getIndexType()
    {
        return $this->index_type;
    }

    /**
     * Set pagesTemplateId.
     *
     * @param int|null $pagesTemplateId
     *
     * @return PagesTemplateSet
     */
    public function setPagesTemplateId($pagesTemplateId = null)
    {
        $this->pages_template_id = $pagesTemplateId;

        return $this;
    }

    /**
     * Get pagesTemplateId.
     *
     * @return int
     */
    public function getPagesTemplateId()
    {
        return $this->pages_template_id;
    }

    /**
     * Set isEnforceSync.
     *
     * @param int|null $isEnforceSync
     *
     * @return PagesTemplateSet
     */
    public function setIsEnforceSync($isEnforceSync = null)
    {
        $this->is_enforce_sync = $isEnforceSync;

        return $this;
    }

    /**
     * Get isEnforceSync.
     *
     * @return int|null
     */
    public function getIsEnforceSync()
    {
        return $this->is_enforce_sync;
    }

    /**
     * Set isOpenRecommend.
     *
     * @param int|null $isOpenRecommend
     *
     * @return PagesTemplateSet
     */
    public function setIsOpenRecommend($isOpenRecommend = null)
    {
        $this->is_open_recommend = $isOpenRecommend;

        return $this;
    }

    /**
     * Get isOpenRecommend.
     *
     * @return int|null
     */
    public function getIsOpenRecommend()
    {
        return $this->is_open_recommend;
    }

    /**
     * Set isOpenWechatappLocation.
     *
     * @param int|null $isOpenWechatappLocation
     *
     * @return PagesTemplateSet
     */
    public function setIsOpenWechatappLocation($isOpenWechatappLocation = null)
    {
        $this->is_open_wechatapp_location = $isOpenWechatappLocation;

        return $this;
    }

    /**
     * Get isOpenWechatappLocation.
     *
     * @return int|null
     */
    public function getIsOpenWechatappLocation()
    {
        return $this->is_open_wechatapp_location;
    }

    /**
     * Set isOpenScanQrcode.
     *
     * @param int|null $isOpenScanQrcode
     *
     * @return PagesTemplateSet
     */
    public function setIsOpenScanQrcode($isOpenScanQrcode = null)
    {
        $this->is_open_scan_qrcode = $isOpenScanQrcode;

        return $this;
    }

    /**
     * Get isOpenScanQrcode.
     *
     * @return int|null
     */
    public function getIsOpenScanQrcode()
    {
        return $this->is_open_scan_qrcode;
    }

    /**
     * Set tabBar.
     *
     * @param string|null $tabBar
     *
     * @return PagesTemplateSet
     */
    public function setTabBar($tabBar = null)
    {
        $this->tab_bar = $tabBar;

        return $this;
    }

    /**
     * Get tabBar.
     *
     * @return string|null
     */
    public function getTabBar()
    {
        return $this->tab_bar;
    }

    /**
     * Set isOpenOfficialAccount.
     *
     * @param int|null $isOpenOfficialAccount
     *
     * @return PagesTemplateSet
     */
    public function setIsOpenOfficialAccount($isOpenOfficialAccount = null)
    {
        $this->is_open_official_account = $isOpenOfficialAccount;

        return $this;
    }

    /**
     * Get isOpenOfficialAccount.
     *
     * @return int|null
     */
    public function getIsOpenOfficialAccount()
    {
        return $this->is_open_official_account;
    }
}
