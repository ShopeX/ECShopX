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
 * ShopMenuRelType 菜单类型关联表
 *
 * @ORM\Table(name="shop_menu_rel_type", options={"comment"="菜单类型关联"}, indexes={
 *    @ORM\Index(name="ix_shopmenu_id", columns={"shopmenu_id"}),
 * })
 * @ORM\Entity(repositoryClass="SuperAdminBundle\Repositories\ShopMenuRelTypeRepository")
 */
class ShopMenuRelType
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="rel_id", type="bigint", options={"comment":"关联ID"})
     */
    private $rel_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shopmenu_id", type="bigint", options={"comment":"菜单id"})
     */
    private $shopmenu_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="menu_type", type="integer",  options={"comment":"菜单类型", "default":1})
     */
    private $menu_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer",  options={"comment":"公司ID"})
     */
    private $company_id;

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
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    protected $updated;

    /**
     * Get relId.
     *
     * @return int
     */
    public function getRelId()
    {
        return $this->rel_id;
    }

    /**
     * Set shopmenuId.
     *
     * @param int $shopmenuId
     *
     * @return ShopMenuRelType
     */
    public function setShopmenuId($shopmenuId)
    {
        $this->shopmenu_id = $shopmenuId;

        return $this;
    }

    /**
     * Get shopmenuId.
     *
     * @return int
     */
    public function getShopmenuId()
    {
        return $this->shopmenu_id;
    }

    /**
     * Set menuType.
     *
     * @param int $menuType
     *
     * @return ShopMenuRelType
     */
    public function setMenuType($menuType)
    {
        $this->menu_type = $menuType;

        return $this;
    }

    /**
     * Get menuType.
     *
     * @return int
     */
    public function getMenuType()
    {
        return $this->menu_type;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ShopMenuRelType
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
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * @param string $companyId
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return ShopMenuRelType
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
