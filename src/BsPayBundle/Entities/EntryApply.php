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

namespace BsPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EntryApply 店铺/经销商 开户申请表
 *
 * @ORM\Table(name="bspay_entry_apply", options={"comment":"开户申请表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_user_id", columns={"user_id"}),
 *    @ORM\Index(name="ix_operator_type", columns={"operator_type"}),
 *    @ORM\Index(name="ix_status", columns={"status"}),
 * })
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\EntryApplyRepository")
 */
class EntryApply
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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", options={"comment":"开户进件ID"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", nullable=true, type="integer", options={"comment":"操作者id", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_type", type="string", options={"comment":"操作者类型:distributor-店铺;dealer-经销;promoter-推广员"})
     */
    private $operator_type;

    /**
     * @var string
     *
     * indv 个人
     * ent 企业
     *
     * @ORM\Column(name="user_type", type="string", length=20, options={"comment":"进件类型", "default": "indv"})
     */
    private $user_type;

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
     * @ORM\Column(name="status", type="string", length=20, nullable=true, options={"comment":"WAIT_APPROVE;APPROVED;REJECT"})
     */
    private $status;

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
     * @return EntryApply
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
     * Set companyId.
     *
     * @param int|null $companyId
     *
     * @return EntryApply
     */
    public function setCompanyId($companyId = null)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set userId.
     *
     * @param string $userId
     *
     * @return EntryApply
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set operatorId.
     *
     * @param int|null $operatorId
     *
     * @return EntryApply
     */
    public function setOperatorId($operatorId = null)
    {
        $this->operator_id = $operatorId;

        return $this;
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

    /**
     * Set operatorType.
     *
     * @param string $operatorType
     *
     * @return EntryApply
     */
    public function setOperatorType($operatorType)
    {
        $this->operator_type = $operatorType;

        return $this;
    }

    /**
     * Get operatorType.
     *
     * @return string
     */
    public function getOperatorType()
    {
        return $this->operator_type;
    }

    /**
     * Set userType.
     *
     * @param string $userType
     *
     * @return EntryApply
     */
    public function setUserType($userType)
    {
        $this->user_type = $userType;

        return $this;
    }

    /**
     * Get userType.
     *
     * @return string
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * Set address.
     *
     * @param string|null $address
     *
     * @return EntryApply
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
     * @return EntryApply
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
     * Set status.
     *
     * @param string|null $status
     *
     * @return EntryApply
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
     * Set created.
     *
     * @param int $created
     *
     * @return EntryApply
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
     * @return EntryApply
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
