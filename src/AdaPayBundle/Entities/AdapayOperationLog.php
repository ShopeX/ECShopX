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
 * AdapayOperationLog adapay操作日志表
 *
 * @ORM\Table(name="adapay_operation_log", options={"comment":"adapay操作日志表"},
 *     indexes={
 *         @ORM\Index(name="idx_id", columns={"company_id","rel_id"}),
 *         @ORM\Index(name="idx_log_type", columns={"log_type"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayOperationLogRepository")
 */
class AdapayOperationLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=false, options={"comment":"日志实际操作者ID", "default": 0})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rel_id", type="bigint", nullable=false, options={"comment":"关联ID,店铺类型为分店ID，经销商为主经销商账号operator_id", "default": 0})
     */
    private $rel_id;

    /**
     * @var string
     *
     * @ORM\Column(name="log_type", type="string", length=50, nullable=false, options={"comment":"merchant-主商户;distributor-店铺;dealer-经销"})
     */
    private $log_type;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255, nullable=false, options={"comment":"日志内容", "default": ""})
     */
    private $content;

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
     * @return AdapayOperationLog
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
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return AdapayOperationLog
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
     * Set relId.
     *
     * @param int $relId
     *
     * @return AdapayOperationLog
     */
    public function setRelId($relId)
    {
        $this->rel_id = $relId;

        return $this;
    }

    /**
     * Get relId.
     *
     * @return int
     */
    public function getRelId()
    {
        return $this->rel_id;
    }

    /**
     * Set logType.
     *
     * @param string $logType
     *
     * @return AdapayOperationLog
     */
    public function setLogType($logType)
    {
        $this->log_type = $logType;

        return $this;
    }

    /**
     * Get logType.
     *
     * @return string
     */
    public function getLogType()
    {
        return $this->log_type;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return AdapayOperationLog
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
     * @return AdapayOperationLog
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
     * @return AdapayOperationLog
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
}
