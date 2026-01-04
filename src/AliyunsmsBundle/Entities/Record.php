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

namespace AliyunsmsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Record 短信记录
 *
 * @ORM\Table(name="aliyunsms_record", options={"comment":"短信发送记录"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AliyunsmsBundle\Repositories\RecordRepository")
 */
class Record
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
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
     * @ORM\Column(name="mobile", type="string", options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var integer
     * @ORM\Column(name="scene_id", type="integer", options={"comment":"场景ID"})
     */
    private $scene_id;

    /**
     * @var integer
     * @ORM\Column(name="task_id", type="integer", options={"comment":"任务ID"})
     */
    private $task_id = 0;

    /**
     * @var string
     * @ORM\Column(name="template_code", type="string", options={"comment":"模板code"})
     */
    private $template_code;

    /**
     * @var string
     * @ORM\Column(name="sms_content", nullable=true, type="string", options={"comment":"短信内容"})
     */
    private $sms_content;

    /**
     * @var string
     * @ORM\Column(name="template_type", type="string", options={"comment":"短信类型:0：验证码;1：短信通知;2：推广短信;"})
     */
    private $template_type;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"发送状态:1-发送中;2-发送失败;3-发送成功"})
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="biz_id", type="string", options={"comment":"发送回执ID"})
     */
    private $biz_id;

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
     * @param int $companyId
     *
     * @return Sign
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set mobile.
     *
     * @param string $mobile
     *
     * @return Record
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }


    /**
     * Set sceneId.
     *
     * @param integer $sceneId
     *
     * @return Record
     */
    public function setSceneId($sceneId)
    {
        $this->scene_id = $sceneId;

        return $this;
    }

    /**
     * Get sceneId.
     *
     * @return integer
     */
    public function getSceneId()
    {
        return $this->scene_id;
    }

    /**
     * Set taskId.
     *
     * @param integer $taskId
     *
     * @return Record
     */
    public function setTaskId($taskId)
    {
        $this->task_id = $taskId;

        return $this;
    }

    /**
     * Get taskId.
     *
     * @return integer
     */
    public function getTaskId()
    {
        return $this->task_id;
    }

    /**
     * Set templateType.
     *
     * @param string $templateType
     *
     * @return Record
     */
    public function setTemplateType($templateType)
    {
        $this->template_type = $templateType;

        return $this;
    }

    /**
     * Get templateType.
     *
     * @return string
     */
    public function getTemplateType()
    {
        return $this->template_type;
    }


    /**
     * Set templateCode.
     *
     * @param string $templateCode
     *
     * @return Record
     */
    public function setTemplateCode($templateCode)
    {
        $this->template_code = $templateCode;

        return $this;
    }

    /**
     * Get templateCode.
     *
     * @return string
     */
    public function getTemplateCode()
    {
        return $this->template_code;
    }


    /**
     * Set smsContent.
     *
     * @param string $smsContent
     *
     * @return Record
     */
    public function setSmsContent($smsContent)
    {
        $this->sms_content = $smsContent;

        return $this;
    }

    /**
     * Get smsContent.
     *
     * @return string
     */
    public function getSmsContent()
    {
        return $this->sms_content;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return Record
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set bizId.
     *
     * @param string $bizId
     *
     * @return Record
     */
    public function setBizId($bizId)
    {
        $this->biz_id = $bizId;

        return $this;
    }

    /**
     * Get bizId.
     *
     * @return string
     */
    public function getBizId()
    {
        return $this->biz_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Record
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
     * @return Record
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
