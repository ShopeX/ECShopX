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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OperatorLogs 商家操作日志表
 *
 * @ORM\Table(name="companys_operator_logs", options={"comment":"商家操作日志表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\OperatorLogsRepository")
 */
class OperatorLogs
{
    /**
     * @var integer
     *
     * @ORM\Column(name="log_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $log_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id","default": 0})
     */
    private $company_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="integer", options={"comment":"操作者id", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="request_uri", type="string", nullable=true, options={"comment":"请求资源信息"})
     */
    private $request_uri;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", nullable=true, options={"comment":"ip地址"})
     */
    private $ip;

    /**
     * @var json_array
     *
     * @ORM\Column(name="params", type="json_array", nullable=true, options={"comment":"请求参数"})
     */
    private $params;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_name", type="string", nullable=true, options={"comment":"操作内容"})
     */
    private $operator_name;

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
    * @var string
    *
    * @ORM\Column(name="log_type", type="string", nullable=true, options={"comment":"操作日志类型","default": "operator"})
    */
    private $log_type;
    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

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
     * @return OperatorLogs
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
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return OperatorLogs
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return integer
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set requestUri
     *
     * @param string $requestUri
     *
     * @return OperatorLogs
     */
    public function setRequestUri($requestUri)
    {
        $this->request_uri = $requestUri;

        return $this;
    }

    /**
     * Get requestUri
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->request_uri;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return OperatorLogs
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set params
     *
     * @param array $params
     *
     * @return OperatorLogs
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
     * Set operatorName
     *
     * @param string $operatorName
     *
     * @return OperatorLogs
     */
    public function setOperatorName($operatorName)
    {
        $this->operator_name = $operatorName;

        return $this;
    }

    /**
     * Get operatorName
     *
     * @return string
     */
    public function getOperatorName()
    {
        return $this->operator_name;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return OperatorLogs
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
     * Set logType
     *
     * @param string $logType
     *
     * @return OperatorLogs
     */
    public function setLogType($logType)
    {
        $this->log_type = $logType;

        return $this;
    }

    /**
     * Get logType
     *
     * @return string
     */
    public function getLogType()
    {
        return $this->log_type;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return OperatorLogs
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return OperatorLogs
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
