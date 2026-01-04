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
 * Task 短信群发任务
 *
 * @ORM\Table(name="aliyunsms_task", options={"comment":"短信群发任务"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AliyunsmsBundle\Repositories\TaskRepository")
 */
class Task
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
     * @ORM\Column(name="task_name", type="string", options={"comment":"任务名称"})
     */
    private $task_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="sign_id", type="integer", options={"comment":"签名ID"})
     */
    private $sign_id;

    /**
     * @var integer
     * @ORM\Column(name="template_id", type="integer", options={"comment":"模板id"})
     */
    private $template_id;

    /**
     * @var string
     *
     * @ORM\Column(name="template_name", type="string", options={"comment":"模板名称"})
     */
    private $template_name;

    /**
     * @var text
     * @ORM\Column(name="user_id", type="text", options={"comment":"会员id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"发送状态:1-等待中;2-发送成功;3-发送失败;4-已撤销"})
     */
    private $status = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="send_at", type="integer", options={"comment":"发送时间"})
     */
    private $send_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_num", type="integer", options={"comment":"号码数量"})
     */
    private $total_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="failed_num", type="integer", options={"comment":"失败号码数量"})
     */
    private $failed_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_send", type="integer", options={"comment":"是否已发送"})
     */
    private $is_send = 0;

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
     * @return Task
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
     * Set taskName.
     *
     * @param string $taskName
     *
     * @return Task
     */
    public function setTaskName($taskName)
    {
        $this->task_name = $taskName;

        return $this;
    }

    /**
     * Get taskName.
     *
     * @return string
     */
    public function getTaskName()
    {
        return $this->task_name;
    }

    /**
     * Set signId.
     *
     * @param integer $signId
     *
     * @return Task
     */
    public function setSignId($signId)
    {
        $this->sign_id = $signId;

        return $this;
    }

    /**
     * Get signId.
     *
     * @return integer
     */
    public function getSignId()
    {
        return $this->sign_id;
    }

    /**
     * Set userId.
     *
     * @param text $userId
     *
     * @return Task
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return text
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set templateId.
     *
     * @param integer templateId
     *
     * @return Task
     */
    public function setTemplateId($templateId)
    {
        $this->template_id = $templateId;

        return $this;
    }

    /**
     * Get templateId.
     *
     * @return integer
     */
    public function getTemplateId()
    {
        return $this->template_id;
    }


    /**
     * Set templateName.
     *
     * @param string $templateName
     *
     * @return Task
     */
    public function setTemplateName($templateName)
    {
        $this->template_name = $templateName;

        return $this;
    }

    /**
     * Get templateName.
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * Set sendAt.
     *
     * @param string $sendAt
     *
     * @return Task
     */
    public function setSendAt($sendAt)
    {
        $this->send_at = $sendAt;

        return $this;
    }

    /**
     * Get sendAt.
     *
     * @return string
     */
    public function getSendAt()
    {
        return $this->send_at;
    }

    /**
     * Set totalNum.
     *
     * @param integer $totalNum
     *
     * @return Task
     */
    public function setTotalNum($totalNum)
    {
        $this->total_num = $totalNum;

        return $this;
    }

    /**
     * Get totalNum.
     *
     * @return integer
     */
    public function getTotalNum()
    {
        return $this->total_num;
    }

    /**
     * Set failedNum.
     *
     * @param integer $failedNum
     *
     * @return Task
     */
    public function setFailedNum($failedNum)
    {
        $this->failed_num = $failedNum;

        return $this;
    }

    /**
     * Get failedNum.
     *
     * @return integer
     */
    public function getFailedNum()
    {
        return $this->failed_num;
    }

    /**
     * Set status.
     *
     * @param integer $status
     *
     * @return Task
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set isSend.
     *
     * @param integer $isSend
     *
     * @return Task
     */
    public function setIsSend($isSend)
    {
        $this->is_send = $isSend;

        return $this;
    }

    /**
     * Get isSend.
     *
     * @return integer
     */
    public function getIsSend()
    {
        return $this->is_send;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Task
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
     * @return Task
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
