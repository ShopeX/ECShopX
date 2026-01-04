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

namespace WorkWechatBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * WorkWechatMessage 企业微信通知
 *
 * @ORM\Table(name="work_wechat_message", options={"comment":"企业微信通知"},
 *    indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *         @ORM\Index(name="idx_operator_id", columns={"operator_id"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="WorkWechatBundle\Repositories\WorkWechatMessageRepository")
 */
class WorkWechatMessage
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"主键"})
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
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"comment":"接受消息用户id"})
     */
    private $operator_id;


    /**
     * @var integer
     *
     * @ORM\Column(name="msg_type", type="smallint", nullable=true, options={"comment":"消息类型:1售后订单，2待发货订单，3未妥投订单"})
     */
    private $msg_type;
    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true, options={"comment":"内容"})
     */
    private $content;
    /**
     * @var integer
     *
     * @ORM\Column(name="add_time", type="integer", nullable=true, options={"comment":"添加时间"})
     */
    private $add_time;
    /**
     * @var integer
     *
     * @ORM\Column(name="up_time", type="integer", nullable=true, options={"comment":"修改时间"})
     */
    private $up_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_read", type="smallint", nullable=true, options={"comment":"是否已读:0,未读;1,已读", "default": 0})
     */
    private $is_read;


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
     * @return WorkWechatMessage
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
     * @return WorkWechatMessage
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
     * @param int operatorId
     *
     * @return WorkWechatMessage
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
     * Set msgType.
     *
     * @param int|null $msgType
     *
     * @return WorkWechatMessage
     */
    public function setMsgType($msgType = null)
    {
        $this->msg_type = $msgType;

        return $this;
    }

    /**
     * Get msgType.
     *
     * @return int|null
     */
    public function getMsgType()
    {
        return $this->msg_type;
    }

    /**
     * Set content.
     *
     * @param string|null $content
     *
     * @return WorkWechatMessage
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set addTime.
     *
     * @param int|null $addTime
     *
     * @return WorkWechatMessage
     */
    public function setAddTime($addTime = null)
    {
        $this->add_time = $addTime;

        return $this;
    }

    /**
     * Get addTime.
     *
     * @return \int
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * Set isRead.
     *
     * @param int|null $isRead
     *
     * @return WorkWechatMessage
     */
    public function setIsRead($isRead = null)
    {
        $this->is_read = $isRead;

        return $this;
    }

    /**
     * Get isRead.
     *
     * @return int|null
     */
    public function getIsRead()
    {
        return $this->is_read;
    }

    /**
     * Set upTime.
     *
     * @param int|null $upTime
     *
     * @return WorkWechatMessage
     */
    public function setUpTime($upTime = null)
    {
        $this->up_time = $upTime;

        return $this;
    }

    /**
     * Get upTime.
     *
     * @return int|null
     */
    public function getUpTime()
    {
        return $this->up_time;
    }
}
