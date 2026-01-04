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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * RightsLog 权益日志表
 *
 * @ORM\Table(name="orders_rights_log", options={"comment":"权益日志表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\RightsLogRepository")
 */
class RightsLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="rights_log_id", type="bigint", options={"comment":"权益日志ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $rights_log_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rights_id", type="bigint", options={"comment":"权益ID"})
     */
    private $rights_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", type="string", options={"comment":"门店ID"})
     */
    private $shop_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rights_name", type="string", length=255, options={"comment":"权益标题"})
     */
    private $rights_name;

    /**
     * @var string
     *
     * @ORM\Column(name="rights_subname", nullable=true, type="string", length=255, options={"comment":"权益子标题"})
     */
    private $rights_subname;

    /**
     * @var integer
     *
     * @ORM\Column(name="consum_num", type="bigint", options={"comment":"消耗次数"})
     */
    private $consum_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendant", type="string", options={"comment":"服务员"})
     */
    private $attendant;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_mobile", length=255, type="string", options={"comment":"核销员手机号"})
     */
    private $salesperson_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="end_time", type="string", options={"comment":"权益结束时间"})
     */
    private $consum_time;

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
     * Get rightsId
     *
     * @return integer
     */
    public function getRightsId()
    {
        return $this->rights_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RightsLog
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
     * Set rightsName
     *
     * @param string $rightsName
     *
     * @return RightsLog
     */
    public function setRightsName($rightsName)
    {
        $this->rights_name = $rightsName;

        return $this;
    }

    /**
     * Get rightsName
     *
     * @return string
     */
    public function getRightsName()
    {
        return $this->rights_name;
    }

    /**
     * Set consumNum
     *
     * @param integer $consumNum
     *
     * @return RightsLog
     */
    public function setConsumNum($consumNum)
    {
        $this->consum_num = $consumNum;

        return $this;
    }

    /**
     * Get consumNum
     *
     * @return integer
     */
    public function getConsumNum()
    {
        return $this->consum_num;
    }

    /**
     * Set attendant
     *
     * @param string $attendant
     *
     * @return RightsLog
     */
    public function setAttendant($attendant)
    {
        $this->attendant = $attendant;

        return $this;
    }

    /**
     * Get attendant
     *
     * @return string
     */
    public function getAttendant()
    {
        return $this->attendant;
    }

    /**
     * Set consumTime
     *
     * @param string $consumTime
     *
     * @return RightsLog
     */
    public function setConsumTime($consumTime)
    {
        $this->consum_time = $consumTime;

        return $this;
    }

    /**
     * Get consumTime
     *
     * @return string
     */
    public function getConsumTime()
    {
        return $this->consum_time;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return RightsLog
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
     * Set salespersonMobile
     *
     * @param string $salespersonMobile
     *
     * @return RightsLog
     */
    public function setSalespersonMobile($salespersonMobile)
    {
        $this->salesperson_mobile = fixedencrypt($salespersonMobile);

        return $this;
    }

    /**
     * Get salespersonMobile
     *
     * @return string
     */
    public function getSalespersonMobile()
    {
        return fixeddecrypt($this->salesperson_mobile);
    }

    /**
     * Set rightsSubname
     *
     * @param string $rightsSubname
     *
     * @return RightsLog
     */
    public function setRightsSubname($rightsSubname)
    {
        $this->rights_subname = $rightsSubname;

        return $this;
    }

    /**
     * Get rightsSubname
     *
     * @return string
     */
    public function getRightsSubname()
    {
        return $this->rights_subname;
    }

    /**
     * Set shopId
     *
     * @param string $shopId
     *
     * @return RightsLog
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return string
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set rightsId
     *
     * @param integer $rightsId
     *
     * @return RightsLog
     */
    public function setRightsId($rightsId)
    {
        $this->rights_id = $rightsId;

        return $this;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return RightsLog
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Get rightsLogId
     *
     * @return integer
     */
    public function getRightsLogId()
    {
        return $this->rights_log_id;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return RightsLog
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
