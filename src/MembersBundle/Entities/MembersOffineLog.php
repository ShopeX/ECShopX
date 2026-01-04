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

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MembersOffineLog 会员线下实体卡日志
 *
 * @ORM\Table(name="members_offine_log", options={"comment"="会员线下实体卡日志"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersOffineLogRepository")
 */
class MembersOffineLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司_ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="offline_card_code", type="string", options={"comment":"实体卡_编号"})
     */
    private $offline_card_code;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", nullable=true, length=50, options={"comment":"姓名"})
     */
    private $username;

    /**
     * @var integer
     *
     *  @ORM\Column(name="sex", type="smallint", nullable=true, options={"comment":"性别。0 未知 1 男 2 女"})
     */
    private $sex;

    /**
     * @var string
     *
     * @ORM\Column(name="grade_id", type="string", nullable=true, length=50, options={"comment":"会员等级"})
     */
    private $grade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="birthday", type="string", nullable=true, length=100, options={"comment":"出生日期"})
     */
    private $birthday;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", nullable=true, length=255, options={"comment":"家庭住址"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true, length=100, options={"comment":"常用邮箱"})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="created_time", type="string", nullable=true, length=100, options={"comment":"入会日期"})
     */
    private $created_time;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MembersOffineLog
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
     * Set offlineCardCode
     *
     * @param string $offlineCardCode
     *
     * @return MembersOffineLog
     */
    public function setOfflineCardCode($offlineCardCode)
    {
        $this->offline_card_code = $offlineCardCode;

        return $this;
    }

    /**
     * Get offlineCardCode
     *
     * @return string
     */
    public function getOfflineCardCode()
    {
        return $this->offline_card_code;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return MembersOffineLog
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set sex
     *
     * @param integer $sex
     *
     * @return MembersOffineLog
     */
    public function setSex($sex)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * Get sex
     *
     * @return integer
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set gradeId
     *
     * @param string $gradeId
     *
     * @return MembersOffineLog
     */
    public function setGradeId($gradeId)
    {
        $this->grade_id = $gradeId;

        return $this;
    }

    /**
     * Get gradeId
     *
     * @return string
     */
    public function getGradeId()
    {
        return $this->grade_id;
    }

    /**
     * Set birthday
     *
     * @param string $birthday
     *
     * @return MembersOffineLog
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return string
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return MembersOffineLog
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return MembersOffineLog
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set createdTime
     *
     * @param string $createdTime
     *
     * @return MembersOffineLog
     */
    public function setCreatedTime($createdTime)
    {
        $this->created_time = $createdTime;

        return $this;
    }

    /**
     * Get createdTime
     *
     * @return string
     */
    public function getCreatedTime()
    {
        return $this->created_time;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MembersOffineLog
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
     * @return MembersOffineLog
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
