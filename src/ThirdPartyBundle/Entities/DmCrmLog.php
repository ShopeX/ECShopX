<?php

namespace ThirdPartyBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * queueLog (达摩crm通信日志)
 *
 * @ORM\Table(name="thirdparty_dmcrm_log", options={"comment"="达摩crm通信日志"})
 * @ORM\Entity(repositoryClass="ThirdPartyBundle\Repositories\DmCrmLogRepository")
 */
class DmCrmLog
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
     * @return SaasErpLog
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
     * @return SaasErpLog
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
     * @return SaasErpLog
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
     * @return SaasErpLog
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
     * @return SaasErpLog
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
     * @return SaasErpLog
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
     * @param string $runtime
     *
     * @return SaasErpLog
     */
    public function setRuntime($runtime)
    {
        $this->runtime = $runtime;

        return $this;
    }

    /**
     * Get runtime
     *
     * @return string
     */
    public function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return SaasErpLog
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
     * @return SaasErpLog
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
