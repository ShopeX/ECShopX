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

//话题:发笔记时选择话题
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Topic
 *
 * @ORM\Table(name="wsugc_topic", options={"comment"="话题"}, indexes={
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_mobile", columns={"mobile"}),
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\TopicRepository")
 *
 */
class Topic
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $topic_id;

    /**
     * @var string
     *
     * @ORM\Column(name="topic_name", type="string", length=250, nullable=false, options={"comment"="话题名称"})
     */
    private $topic_name;


    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=50, nullable=true, options={"comment"="手机号"})
     */
    private $mobile;


    /**
     * @var int
     *
     * @ORM\Column(name="p_order", type="integer", nullable=false, options={"comment"="排序","default":0})
     */
    private $p_order ;

    /**
     * @var int
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="integer", nullable=false, options={"unsigned"=true,"comment"="添加时间"})
     */
    private $created;

 /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    /**
     * @var \DateTime $ai_verify_time
     *
     * @ORM\Column(name="ai_verify_time", type="bigint", nullable=true,options={"comment":"机器审核时间","default":"0"})
     */
    protected $ai_verify_time;    


    /**
     * @var \DateTime $manual_verify_time
     *
     * @ORM\Column(name="manual_verify_time", type="bigint", nullable=true,options={"comment":"人工审核时间","default":"0"})
     */
    protected $manual_verify_time; 

    
    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer", options={"comment":"是否启用.", "default": 1})
     */
    private $enabled = 1;


        /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"审核状态(0待审核,1审核通过,2机器拒绝,3待人工审核,4人工拒绝)", "default": 0})
     */
    private $status = '0';

        /**
     * @var string
     *
     * @ORM\Column(name="ai_refuse_reason", type="string",nullable=true, options={"comment":"机器拒绝理由"})
     */
    private $ai_refuse_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="manual_refuse_reason", type="string",nullable=true, options={"comment":"人工拒绝理由"})
     */
    private $manual_refuse_reason;

    
    /**
     * @var integer
     *
     * @ORM\Column(name="is_top", type="integer",nullable=false, options={"comment":"是否置顶.", "default": 0})
     */
    private $is_top;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint",options={"comment":"创建的用户", "default": 0})
     */
    private $user_id;

    /**
     * @var source
     *
     * @ORM\Column(name="source", type="integer",nullable=true,options={"comment":"来源 1用户,2官方", "default": "1"})
     */
    private $source;

     /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint",nullable=true,options={"comment":"管理员id", "default": 0})
     */
    private $operator_id;

    /**
     * Get topicId.
     *
     * @return int
     */
    public function getTopicId()
    {
        return $this->topic_id;
    }

    /**
     * Set topicName.
     *
     * @param string $topicName
     *
     * @return Topic
     */
    public function setTopicName($topicName)
    {
        $this->topic_name = $topicName;

        return $this;
    }

    /**
     * Get topicName.
     *
     * @return string
     */
    public function getTopicName()
    {
        return $this->topic_name;
    }

    /**
     * Set pOrder.
     *
     * @param int $pOrder
     *
     * @return Topic
     */
    public function setPOrder($pOrder)
    {
        $this->p_order = $pOrder;

        return $this;
    }

    /**
     * Get pOrder.
     *
     * @return int
     */
    public function getPOrder()
    {
        return $this->p_order;
    }

    /**
     * Set createtime.
     *
     * @param int $createtime
     *
     * @return Topic
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
     * @return WsugcPost
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

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Topic
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
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return Topic
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set source.
     *
     * @param int $source
     *
     * @return Topic
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set isTop.
     *
     * @param int $isTop
     *
     * @return Topic
     */
    public function setIsTop($isTop)
    {
        $this->is_top = $isTop;

        return $this;
    }

    /**
     * Get isTop.
     *
     * @return int
     */
    public function getIsTop()
    {
        return $this->is_top;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return Topic
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set mobile.
     *
     * @param string|null $mobile
     *
     * @return Topic
     */
    public function setMobile($mobile = null)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string|null
     */
    public function getMobile()
    {
        return $this->mobile;
    }
        /**
     * Set verifyTime.
     *
     * @param int|null $verifyTime
     *
     * @return WsugcPost
     */
    public function setAiVerifyTime($aiVerifyTime = null)
    {
        $this->ai_verify_time = $aiVerifyTime;

        return $this;
    }

    /**
     * Get AiVerifyTime.
     *
     * @return int|null
     */
    public function getAiVerifyTime()
    {
        return $this->ai_verify_time;
    }


        /**
     * Set verifyTime.
     *
     * @param int|null $verifyTime
     *
     * @return WsugcPost
     */
    public function setManualVerifyTime($manualVerifyTime = null)
    {
        $this->manual_verify_time = $manualVerifyTime;

        return $this;
    }

    /**
     * Get manualVerifyTime.
     *
     * @return int|null
     */
    public function getManualVerifyTime()
    {
        return $this->manual_verify_time;
    }
       /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return WsugcPost
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
     * Set aiRefuseReason.
     *
     * @param string|null $aiRefuseReason
     *
     * @return Comment
     */
    public function setAiRefuseReason($aiRefuseReason = null)
    {
        $this->ai_refuse_reason = $aiRefuseReason;

        return $this;
    }

    /**
     * Get aiRefuseReason.
     *
     * @return string|null
     */
    public function getAiRefuseReason()
    {
        return $this->ai_refuse_reason;
    }

    /**
     * Set manualRefuseReason.
     *
     * @param string|null $manualRefuseReason
     *
     * @return Comment
     */
    public function setManualRefuseReason($manualRefuseReason = null)
    {
        $this->manual_refuse_reason = $manualRefuseReason;

        return $this;
    }

    /**
     * Get manualRefuseReason.
     *
     * @return string|null
     */
    public function getManualRefuseReason()
    {
        return $this->manual_refuse_reason;
    }
     /**
     * Set status.
     *
     * @param int $status
     *
     * @return WsugcPost
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

}
