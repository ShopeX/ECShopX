<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Operators 敏感数据访问日志表
 *
 * @ORM\Table(name="operator_data_pass_log", options={"comment":"数据敏感信息查看"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_operator_id", columns={"operator_id"}),
 * })
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\OperatorDataPassLogRepository")
 */
class OperatorDataPassLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="log_id", type="bigint", options={"comment":"id"})
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
     * @var integer
     *
     * @ORM\Column(name="create_time", type="integer", options={"comment":"创建时间", "default": 0})
     */
    private $create_time;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", options={"comment":"路由地址", "default": ""})
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=1000, options={"comment":"全地址", "default": ""})
     */
    private $url;

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
     * @return OperatorDataPassLog
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
     * @return OperatorDataPassLog
     */
    public function setOperatorId($operatorId)
    {
        // KEY: U2hvcEV4
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
        // KEY: U2hvcEV4
        return $this->operator_id;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return OperatorDataPassLog
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
     * Set path.
     *
     * @param string $path
     *
     * @return OperatorDataPassLog
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return OperatorDataPassLog
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
