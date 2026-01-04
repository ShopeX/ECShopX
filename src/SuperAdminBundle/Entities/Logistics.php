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
 * Logistics 物流公司表
 *
 * @ORM\Table(name="logistics", options={"comment":"物流公司表"})
 * @ORM\Entity(repositoryClass="SuperAdminBundle\Repositories\LogisticsRepository")
 */
class Logistics
{
    /**
     * @var integer
     *
     * @ORM\Column(name="corp_id", type="smallint", options={"comment":"物流公司ID"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $corp_id;

    /**
     * @var string
     *
     * @ORM\Column(name="corp_code", type="string", options={"comment":"快递鸟代码"})
     *
     */
    private $corp_code;

    /**
     * @var string
     *
     * @ORM\Column(name="kuaidi_code", type="string", options={"comment":"快递100代码"})
     *
     */
    private $kuaidi_code;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", options={"comment":"物流公司全名"})
     */
    private $full_name;

    /**
     * @var string
     *
     * @ORM\Column(name="corp_name", type="string", options={"comment":"物流公司简称"})
     */
    private $corp_name;


    /**
     * @var integer
     *
     * @ORM\Column(name="order_sort", type="smallint", options={"comment":"排序", "default":99})
     */
    private $order_sort = 99;

    /**
     * @var boolean
     *
     * @ORM\Column(name="custom", type="boolean", options={"comment":"是否自定义", "default":false},nullable=true)
     */
    private $custom = 0;

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
     * @var string
     *
     * @ORM\Column(name="phone", type="string", nullable=true, length=20, options={"comment":"物流公司电话"})
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", nullable=true, options={"comment":"logo"})
     */
    protected $logo;

    /**
     * Get corpId
     *
     * @return integer
     */
    public function getCorpId()
    {
        return $this->corp_id;
    }

    /**
     * Set corpCode
     *
     * @param string $corpCode
     *
     * @return Logistics
     */
    public function setCorpCode($corpCode)
    {
        $this->corp_code = $corpCode;

        return $this;
    }

    /**
     * Get corpCode
     *
     * @return string
     */
    public function getCorpCode()
    {
        return $this->corp_code;
    }

    /**
     * Set fullName
     *
     * @param string $fullName
     *
     * @return Logistics
     */
    public function setFullName($fullName)
    {
        $this->full_name = $fullName;

        return $this;
    }

    /**
     * Get fullName
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->full_name;
    }

    /**
     * Set corpName
     *
     * @param string $corpName
     *
     * @return Logistics
     */
    public function setCorpName($corpName)
    {
        $this->corp_name = $corpName;

        return $this;
    }

    /**
     * Get corpName
     *
     * @return string
     */
    public function getCorpName()
    {
        return $this->corp_name;
    }

    /**
     * Set orderSort
     *
     * @param integer $orderSort
     *
     * @return Logistics
     */
    public function setOrderSort($orderSort)
    {
        $this->order_sort = $orderSort;

        return $this;
    }

    /**
     * Get orderSort
     *
     * @return integer
     */
    public function getOrderSort()
    {
        return $this->order_sort;
    }

    /**
     * Set custom
     *
     * @param boolean $custom
     *
     * @return Logistics
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;

        return $this;
    }

    /**
     * Get custom
     *
     * @return boolean
     */
    public function getCustom()
    {
        return $this->custom;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Logistics
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
     * @return Logistics
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
     * Set kuaidiCode
     *
     * @param string $kuaidiCode
     *
     * @return Logistics
     */
    public function setKuaidiCode($kuaidiCode)
    {
        $this->kuaidi_code = $kuaidiCode;

        return $this;
    }

    /**
     * Get kuaidiCode
     *
     * @return string
     */
    public function getKuaidiCode()
    {
        return $this->kuaidi_code;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Logistics
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set logo
     *
     * @param string $logo
     *
     * @return Logistics
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }
}
