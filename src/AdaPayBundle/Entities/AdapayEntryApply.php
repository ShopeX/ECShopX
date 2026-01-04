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

namespace AdaPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AdapayEntryApply 店铺/经销商 开户申请表
 *
 * @ORM\Table(name="adapay_entry_apply", options={"comment":"开户申请表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayEntryApplyRepository")
 */
class AdapayEntryApply
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", type="string", length=64, options={"comment":"用户名"})
     */
    private $user_name;


    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="string", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="entry_id", type="string", options={"comment":"开户进件ID"})
     */
    private $entry_id;

    /**
     * @var string
     *
     * @ORM\Column(name="apply_type", type="string", options={"comment":"申请类型:dealer;distributor"})
     */
    private $apply_type;


    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", nullable=true, options={"comment":"所属地区"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="text", nullable=true, options={"comment":"审批意见"})
     */
    private $comments;

    /**
     * @var string
     *
     * @ORM\Column(name="is_sms", type="string", length=20, nullable=true, options={"comment":"是否短信提醒: 1:是  0:否"})
     */
    private $is_sms;


    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=true, options={"comment":"WAIT_APPROVE;APPROVED;REJECT"})
     */
    private $status;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;


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
     * Set userName.
     *
     * @param string $userName
     *
     * @return AdapayDivFee
     */
    public function setUserName($userName)
    {
        $this->user_name = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set applyType.
     *
     * @param string|null $applyType
     *
     * @return AdapayDivFee
     */
    public function setApplyType($applyType = null)
    {
        $this->apply_type = $applyType;

        return $this;
    }

    /**
     * Get applyType.
     *
     * @return string|null
     */
    public function getApplyType()
    {
        return $this->apply_type;
    }

    /**
     * Set companyId.
     *
     * @param string $companyId
     *
     * @return AdapayDivFee
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set entryId.
     *
     * @param string $entryId
     *
     * @return AdapayDivFee
     */
    public function setEntryId($entryId)
    {
        $this->entry_id = $entryId;

        return $this;
    }

    /**
     * Get entryId.
     *
     * @return string
     */
    public function getEntryId()
    {
        return $this->entry_id;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return AdapayMerchantEntry
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayDivFee
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime.
     *
     * @return int
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime.
     *
     * @param int|null $updateTime
     *
     * @return AdapayDivFee
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime.
     *
     * @return int|null
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return AdapayEntryApply
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set address.
     *
     * @param string|null $address
     *
     * @return AdapayEntryApply
     */
    public function setAddress($address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set comments.
     *
     * @param string|null $comments
     *
     * @return AdapayEntryApply
     */
    public function setComments($comments = null)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments.
     *
     * @return string|null
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set isSms.
     *
     * @param string|null $isSms
     *
     * @return AdapayEntryApply
     */
    public function setIsSms($isSms = null)
    {
        $this->is_sms = $isSms;

        return $this;
    }

    /**
     * Get isSms.
     *
     * @return string|null
     */
    public function getIsSms()
    {
        return $this->is_sms;
    }
}
