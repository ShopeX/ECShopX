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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SalespersonOperatorLog 商家操作日志表
 *
 * @ORM\Table(name="salesperson_operator_log", options={"comment":"商家操作日志表"})
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonOperatorLogRepository")
 */
class SalespersonOperatorLog
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
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id","default": 0})
     */
    private $distributor_id = 0;

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
     * Get logId.
     *
     * @return int
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalespersonOperatorLog
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return SalespersonOperatorLog
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return SalespersonOperatorLog
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set requestUri.
     *
     * @param string|null $requestUri
     *
     * @return SalespersonOperatorLog
     */
    public function setRequestUri($requestUri = null)
    {
        $this->request_uri = $requestUri;

        return $this;
    }

    /**
     * Get requestUri.
     *
     * @return string|null
     */
    public function getRequestUri()
    {
        return $this->request_uri;
    }

    /**
     * Set ip.
     *
     * @param string|null $ip
     *
     * @return SalespersonOperatorLog
     */
    public function setIp($ip = null)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string|null
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set params.
     *
     * @param array|null $params
     *
     * @return SalespersonOperatorLog
     */
    public function setParams($params = null)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params.
     *
     * @return array|null
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set operatorName.
     *
     * @param string|null $operatorName
     *
     * @return SalespersonOperatorLog
     */
    public function setOperatorName($operatorName = null)
    {
        $this->operator_name = $operatorName;

        return $this;
    }

    /**
     * Get operatorName.
     *
     * @return string|null
     */
    public function getOperatorName()
    {
        return $this->operator_name;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SalespersonOperatorLog
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
}
