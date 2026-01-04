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

namespace WorkWechatBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WorkWechatRelLogs 企业微信用户关联关系日志
 *
 * @ORM\Table(name="work_wechat_rel_logs", options={"comment":"企业微信用户关联关系日志"})
 * @ORM\Entity(repositoryClass="WorkWechatBundle\Repositories\WorkWechatRelLogsRepository")
 */
class WorkWechatRelLogs
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"企业微信用户关联表id"})
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
     * @ORM\Column(name="work_userid", type="string",  options={"comment":"通讯录成员id", "default": ""})
     */
    private $work_userid;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购员id", "default": 0})
     */
    private $salesperson_id;

    /**
     * @var string
     *
     * @ORM\Column(name="external_userid", type="string", options={"comment":"企业微信外部成员id", "default": ""})
     */
    private $external_userid;

    /**
     * @var string
     *
     * @ORM\Column(name="unionid", type="string", options={"comment":"企业微信外部成员unionid", "default": ""})
     */
    private $unionid;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员id", "default": 0})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_friend", type="boolean", options={"comment":"是否好友 0 否 1 是", "default": 0})
     */
    private $is_friend;

    /**
     * @var string
     *
     * @ORM\Column(name="remarks", length=60, type="string", options={"comment":"企业微信用户关联关系日志"})
     */
    private $remarks;

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
     * Set id
     *
     * @param integer $id
     *
     * @return WorkWechatRel
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set workUserid
     *
     * @param integer $workUserid
     *
     * @return WorkWechatRel
     */
    public function setWorkUserid($workUserid)
    {
        $this->work_userid = $workUserid;

        return $this;
    }

    /**
     * Get workUserid
     *
     * @return integer
     */
    public function getWorkUserid()
    {
        return $this->work_userid;
    }

    /**
     * Set salespersonId
     *
     * @param integer $salespersonId
     *
     * @return WorkWechatRel
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId
     *
     * @return integer
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set externalUserid
     *
     * @param integer $externalUserid
     *
     * @return WorkWechatRel
     */
    public function setExternalUserid($externalUserid)
    {
        $this->external_userid = $externalUserid;

        return $this;
    }

    /**
     * Get externalUserid
     *
     * @return integer
     */
    public function getExternalUserid()
    {
        return $this->external_userid;
    }

    /**
     * Set unionid
     *
     * @param integer $unionid
     *
     * @return WorkWechatRel
     */
    public function setUnionid($unionid)
    {
        $this->unionid = $unionid;

        return $this;
    }

    /**
     * Get unionid
     *
     * @return integer
     */
    public function getUnionid()
    {
        return $this->unionid;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return WorkWechatRel
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
     * Set isFriend
     *
     * @param integer $isFriend
     *
     * @return WorkWechatRel
     */
    public function setIsFriend($isFriend)
    {
        $this->is_friend = $isFriend;

        return $this;
    }

    /**
     * Get isFriend
     *
     * @return integer
     */
    public function getIsFriend()
    {
        return $this->is_friend;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     *
     * @return WorkWechatRelLogs
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return WorkWechatRelLogs
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WorkWechatRelLogs
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
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return WorkWechatRelLogs
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
