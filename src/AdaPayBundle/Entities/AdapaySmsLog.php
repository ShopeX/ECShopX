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
 * AdapaySmsLog adapay短信提醒日志表
 *
 * @ORM\Table(name="adapay_sms_log", options={"comment":"adapay短信提醒日志表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_rel_id", columns={"rel_id"}),
 *    @ORM\Index(name="ix_usr_phone", columns={"usr_phone"}),
 * })
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapaySmsLogRepository")
 */
class AdapaySmsLog
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
     * @ORM\Column(name="company_id", type="string", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_phone", type="string", options={"comment":"发送手机号"})
     */
    private $usr_phone;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_id", type="string", options={"comment":"如果type(提醒类型)为1或2 此字段为开户的member_id,如果type(提醒类型)为3 此字段为dealer_id"})
     */
    private $rel_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", options={"comment":"提醒类型 总商户开户提醒:1  子商户开户提醒:2  重置密码提醒:3"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", options={"comment":"发送内容"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=true, options={"comment":"发送状态 1:成功  0:失败"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_info", type="text", nullable=true, options={"comment":"错误信息"})
     */
    private $error_info;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;


    /**
     * Set id.
     *
     * @param int $id
     *
     * @return AdapayDivFee
     */
    public function setId($id)
    {
        // KEY: U2hvcEV4
        $this->id = $id;

        return $this;
    }

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
     * @param string $companyId
     *
     * @return AdapaySmsLog
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
     * Set usrPhone.
     *
     * @param string $usrPhone
     *
     * @return AdapaySmsLog
     */
    public function setUsrPhone($usrPhone)
    {
        // KEY: U2hvcEV4
        $this->usr_phone = $usrPhone;

        return $this;
    }

    /**
     * Get usrPhone.
     *
     * @return string
     */
    public function getUsrPhone()
    {
        return $this->usr_phone;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return AdapaySmsLog
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapaySmsLog
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
     * @return AdapaySmsLog
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
     * Set relId.
     *
     * @param string $relId
     *
     * @return AdapaySmsLog
     */
    public function setRelId($relId)
    {
        $this->rel_id = $relId;

        return $this;
    }

    /**
     * Get relId.
     *
     * @return string
     */
    public function getRelId()
    {
        return $this->rel_id;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return AdapaySmsLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return AdapaySmsLog
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
     * Set errorInfo.
     *
     * @param string|null $errorInfo
     *
     * @return AdapaySmsLog
     */
    public function setErrorInfo($errorInfo = null)
    {
        $this->error_info = $errorInfo;

        return $this;
    }

    /**
     * Get errorInfo.
     *
     * @return string|null
     */
    public function getErrorInfo()
    {
        return $this->error_info;
    }
}
