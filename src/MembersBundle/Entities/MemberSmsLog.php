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
 * MemberSmsLog 群发短信log
 *
 * @ORM\Table(name="members_msmsend_log", options={"comment":"群发短信log"})
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersSmsLogRepository")
 */
class MemberSmsLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="log_id", type="bigint", options={"comment":"用户id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $log_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="send_to_phones", type="text", options={"comment":"收信手机号"})
     */
    private $send_to_phones;

    /**
     * @var string
     *
     * @ORM\Column(name="sms_content", type="string", length=256, options={"comment":"短信内容"})
     */
    private $sms_content;

    /**
     * @var string
     *
     * @ORM\Column(name="operator", type="string", length=50, options={"comment":"操作员信息"})
     */
    private $operator;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"状态"})
     */
    private $status = 1;

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
     * Get logId
     *
     * @return integer
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberSmsLog
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
     * Set sendToPhones
     *
     * @param string $sendToPhones
     *
     * @return MemberSmsLog
     */
    public function setSendToPhones($sendToPhones)
    {
        $this->send_to_phones = $sendToPhones;

        return $this;
    }

    /**
     * Get sendToPhones
     *
     * @return string
     */
    public function getSendToPhones()
    {
        return $this->send_to_phones;
    }

    /**
     * Set smsContent
     *
     * @param string $smsContent
     *
     * @return MemberSmsLog
     */
    public function setSmsContent($smsContent)
    {
        $this->sms_content = $smsContent;

        return $this;
    }

    /**
     * Get smsContent
     *
     * @return string
     */
    public function getSmsContent()
    {
        return $this->sms_content;
    }

    /**
     * Set operator
     *
     * @param string $operator
     *
     * @return MemberSmsLog
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return MemberSmsLog
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MemberSmsLog
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
     * @return MemberSmsLog
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
