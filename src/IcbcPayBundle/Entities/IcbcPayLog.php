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

namespace IcbcPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * IcbcPayLog 工商银行接口日志
 *
 * @ORM\Table(name="icbc_pay_log", options={"comment":"工商银行接口日志"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *    @ORM\Index(name="idx_unique_key", columns={"unique_key"}),
 *    @ORM\Index(name="idx_add_time", columns={"add_time"}),
 * })
 * @ORM\Entity(repositoryClass="IcbcPayBundle\Repositories\IcbcPayLogRepository")
 */
class IcbcPayLog
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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, nullable=true, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="unique_key", type="string", length=64, nullable=true, options={"comment":"唯一ID", "default":""})
     */
    private $unique_key = '';

    /**
     * @var string
     *
     * request 请求
     * callback 回调
     *
     * @ORM\Column(name="log_type", type="string", length=20, nullable=true, options={"comment":"日志类型"})
     */
    private $log_type;

    /**
     * @var string
     *
     * @ORM\Column(name="log_data", type="text", nullable=true, options={"comment":"日志内容"})
     */
    private $log_data = "";

    /**
     * @var string
     *
     * @ORM\Column(name="api_res", type="text", nullable=true, options={"comment":"请求结果"})
     */
    private $api_res = "";

    /**
     * @var \DateTime $add_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true, options={"comment":"创建时间"})
     */
    private $add_time;

    /**
     * @var \DateTime $modify_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true, options={"comment":"更新时间"})
     */
    private $modify_time;


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
     * @return LitePosLog
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
     * Set orderId.
     *
     * @param string|null $orderId
     *
     * @return LitePosLog
     */
    public function setOrderId($orderId = null)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set uniqueKey.
     *
     * @param string|null $uniqueKey
     *
     * @return LitePosLog
     */
    public function setUniqueKey($uniqueKey = null)
    {
        $this->unique_key = $uniqueKey;

        return $this;
    }

    /**
     * Get uniqueKey.
     *
     * @return string|null
     */
    public function getUniqueKey()
    {
        return $this->unique_key;
    }

    /**
     * Set logType.
     *
     * @param string|null $logType
     *
     * @return LitePosLog
     */
    public function setLogType($logType = null)
    {
        $this->log_type = $logType;

        return $this;
    }

    /**
     * Get logType.
     *
     * @return string|null
     */
    public function getLogType()
    {
        return $this->log_type;
    }

    /**
     * Set logData.
     *
     * @param string|null $logData
     *
     * @return LitePosLog
     */
    public function setLogData($logData = null)
    {
        $this->log_data = $logData;

        return $this;
    }

    /**
     * Get logData.
     *
     * @return string|null
     */
    public function getLogData()
    {
        return $this->log_data;
    }

    /**
     * Set apiRes.
     *
     * @param string|null $apiRes
     *
     * @return LitePosLog
     */
    public function setApiRes($apiRes = null)
    {
        $this->api_res = $apiRes;

        return $this;
    }

    /**
     * Get apiRes.
     *
     * @return string|null
     */
    public function getApiRes()
    {
        return $this->api_res;
    }

    /**
     * Set addTime.
     *
     * @param \DateTime|null $addTime
     *
     * @return LitePosLog
     */
    public function setAddTime($addTime = null)
    {
        $this->add_time = $addTime;

        return $this;
    }

    /**
     * Get addTime.
     *
     * @return \DateTime|null
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * Set modifyTime.
     *
     * @param \DateTime|null $modifyTime
     *
     * @return LitePosLog
     */
    public function setModifyTime($modifyTime = null)
    {
        $this->modify_time = $modifyTime;

        return $this;
    }

    /**
     * Get modifyTime.
     *
     * @return \DateTime|null
     */
    public function getModifyTime()
    {
        return $this->modify_time;
    }
}
