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

namespace SystemLinkBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OmsQueueLog (联通oms通信日志)
 *
 * @ORM\Table(name="systemlink_oms_queuelog", options={"comment"="联通oms通信日志"})
 * @ORM\Entity(repositoryClass="SystemLinkBundle\Repositories\OmsQueueLogRepository")
 */
class OmsQueueLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint",options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint",options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="api_type", type="string", options={"comment":"日志同步类型, response:响应，request:请求"})
     */
    private $api_type;
    /**
     * @var string
     *
     * @ORM\Column(name="worker", type="string", options={"comment":"api"})
     */
    private $worker;

    /**
     * @var string
     *
     * @ORM\Column(name="params", type="json_array", nullable=true, options={"comment":"任务参数"})
     */
    private $params;

    /**
     * @var string
     *
     * @ORM\Column(name="result", type="json_array", nullable=true, options={"comment":"返回数据"})
     */
    private $result;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"运行状态：running,success,fail", "default":"running"})
     */
    private $status = 'running';

    /**
     * @var string
     *
     * @ORM\Column(name="runtime", type="string", nullable=true, options={"comment":"运行时间(秒)"})
     */
    private $runtime;

    /**
     * @var string
     *
     * @ORM\Column(name="msg_id", type="string", nullable=true, options={"comment":"msg_id"})
     */
    private $msg_id;

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
     * @return OmsQueueLog
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
     * Set apiType
     *
     * @param string $apiType
     *
     * @return OmsQueueLog
     */
    public function setApiType($apiType)
    {
        $this->api_type = $apiType;

        return $this;
    }

    /**
     * Get apiType
     *
     * @return string
     */
    public function getApiType()
    {
        return $this->api_type;
    }

    /**
     * Set worker
     *
     * @param string $worker
     *
     * @return OmsQueueLog
     */
    public function setWorker($worker)
    {
        $this->worker = $worker;

        return $this;
    }

    /**
     * Get worker
     *
     * @return string
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * Set params
     *
     * @param array $params
     *
     * @return OmsQueueLog
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set result
     *
     * @param array $result
     *
     * @return OmsQueueLog
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result
     *
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return OmsQueueLog
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set runtime
     *
     * @param integer $runtime
     *
     * @return OmsQueueLog
     */
    public function setRuntime($runtime)
    {
        $this->runtime = $runtime;

        return $this;
    }

    /**
     * Get runtime
     *
     * @return integer
     */
    public function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * Set msgId
     *
     * @param string $msgId
     *
     * @return OmsQueueLog
     */
    public function setMsgId($msgId)
    {
        $this->msg_id = $msgId;

        return $this;
    }

    /**
     * Get msgId
     *
     * @return string
     */
    public function getMsgId()
    {
        return $this->msg_id;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return OmsQueueLog
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
     * @return OmsQueueLog
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
