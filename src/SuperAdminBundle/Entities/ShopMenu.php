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
 * ShopMenu 商家和平台端菜单管理
 *
 * @ORM\Table(name="shop_menu", options={"comment"="商家和平台端菜单管理"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="SuperAdminBundle\Repositories\ShopMenuRepository")
 */
class ShopMenu
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="shopmenu_id", type="bigint", options={"comment":"菜单id"})
     */
    private $shopmenu_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="integer", options={"comment":"公司id，company_id为0的时候表示通用"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="alias_name", type="string", nullable=true, options={"comment":"菜单别名,唯一值"})
     */
    private $alias_name;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", options={"comment":"菜单名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", options={"comment":"菜单对应路由"})
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="sort", type="integer", nullable=true, options={"comment":"排序"})
     */
    private $sort;

    /**
     * @var string
     *
     * @ORM\Column(name="is_menu", type="boolean", nullable=true, options={"comment":"是否为菜单"})
     */
    private $is_menu;

    /**
     * @var string
     *
     * @ORM\Column(name="pid", type="bigint", options={"comment":"上级菜单id", "default":0})
     */
    private $pid = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="apis", type="text", nullable=true, options={"comment":"API权限集"})
     */
    private $apis;

    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", nullable=true, options={"comment":"菜单图标"})
     */
    private $icon;

    /**
     * @var string
     *
     * @ORM\Column(name="is_show", type="boolean", options={"comment":"是否显示"})
     */
    private $is_show;

    /**
     * @var integer
     *
     * @ORM\Column(name="version", type="smallint", options={"comment":"菜单版本,1:平台菜单;2:IT端菜单,3:店铺菜单,4:供应商菜单,5:经销商菜单,6:商户菜单", "default":1})
     */
    private $version = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"是否有效"})
     */
    private $disabled;

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
     * Get shopmenuId
     *
     * @return integer
     */
    public function getShopmenuId()
    {
        return $this->shopmenu_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ShopMenu
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
     * Set name
     *
     * @param string $name
     *
     * @return ShopMenu
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return ShopMenu
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return ShopMenu
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
     * Set isMenu
     *
     * @param integer $isMenu
     *
     * @return ShopMenu
     */
    public function setIsMenu($isMenu)
    {
        $this->is_menu = $isMenu;

        return $this;
    }

    /**
     * Get isMenu
     *
     * @return integer
     */
    public function getIsMenu()
    {
        return $this->is_menu;
    }

    /**
     * Set pid
     *
     * @param integer $pid
     *
     * @return ShopMenu
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get pid
     *
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set apis
     *
     * @param string $apis
     *
     * @return ShopMenu
     */
    public function setApis($apis)
    {
        $this->apis = $apis;

        return $this;
    }

    /**
     * Get apis
     *
     * @return string
     */
    public function getApis()
    {
        return $this->apis;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ShopMenu
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
     * @return ShopMenu
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
     * Set icon
     *
     * @param boolean $icon
     *
     * @return ShopMenu
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return boolean
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set isShow
     *
     * @param boolean $isShow
     *
     * @return ShopMenu
     */
    public function setIsShow($isShow)
    {
        $this->is_show = $isShow;

        return $this;
    }

    /**
     * Get isShow
     *
     * @return boolean
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * Set version
     *
     * @param string $version
     *
     * @return ShopMenu
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return ShopMenu
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set aliasName
     *
     * @param string $aliasName
     *
     * @return ShopMenu
     */
    public function setAliasName($aliasName)
    {
        $this->alias_name = $aliasName;

        return $this;
    }

    /**
     * Get aliasName
     *
     * @return string
     */
    public function getAliasName()
    {
        return $this->alias_name;
    }

    /**
     * Set shopmenuId.
     *
     * @param int $shopmenuId
     *
     * @return ShopMenu
     */
    public function setShopmenuId($shopmenuId)
    {
        $this->shopmenu_id = $shopmenuId;

        return $this;
    }
}
