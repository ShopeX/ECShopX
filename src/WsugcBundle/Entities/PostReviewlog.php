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

//笔记审核日志
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PostReviewLog 笔记审核日志
 *
 * @ORM\Table(name="wsugc_post_reviewlog", options={"comment"="笔记审核日志"}, indexes={
 *    @ORM\Index(name="idx_post_id", columns={"post_id"}),
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
 * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\PostReviewLogRepository")
 */
class PostReviewLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="log_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $log_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint", nullable=true, options={"comment":"笔记id"})
     */
    private $post_id;


     /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"操作员id"})
     */
    private $operator_id;


    /**
     * @var integer
     *
     * @ORM\Column(name="operator", type="string", nullable=true, options={"comment":"操作员名称或手机"})
     */
    private $operator;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="record_date", type="integer", options={"comment":"记录提交日期"})
     */
    private $record_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id"})
     */
    private $shop_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="temp_id", type="bigint", nullable=true, options={"comment":"模板id"})
     */
    private $temp_id;

    /**
     * @var string
     *
     * @ORM\Column(name="form_data", type="text", options={"comment":"记录表单内容"})
     */
    private $form_data;

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
     * @return UserDailyRecord
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
     * @param int|null $operatorId
     *
     * @return UserDailyRecord
     */
    public function setOperatorId($operatorId = null)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int|null
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }
    /**
     * Set operator.
     *
     * @param string|null $operator
     *
     * @return UserDailyRecord
     */
    public function setOperator($operator = null)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator.
     *
     * @return string|null
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return UserDailyRecord
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
     * Set recordDate.
     *
     * @param int $recordDate
     *
     * @return UserDailyRecord
     */
    public function setRecordDate($recordDate)
    {
        $this->record_date = $recordDate;

        return $this;
    }

    /**
     * Get recordDate.
     *
     * @return int
     */
    public function getRecordDate()
    {
        return $this->record_date;
    }

    /**
     * Set shopId.
     *
     * @param int|null $shopId
     *
     * @return UserDailyRecord
     */
    public function setShopId($shopId = null)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId.
     *
     * @return int|null
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set tempId.
     *
     * @param int|null $tempId
     *
     * @return UserDailyRecord
     */
    public function setTempId($tempId = null)
    {
        $this->temp_id = $tempId;

        return $this;
    }

    /**
     * Get tempId.
     *
     * @return int|null
     */
    public function getTempId()
    {
        return $this->temp_id;
    }

    /**
     * Set formData.
     *
     * @param string $formData
     *
     * @return UserDailyRecord
     */
    public function setFormData($formData)
    {
        $this->form_data = $formData;

        return $this;
    }

    /**
     * Get formData.
     *
     * @return string
     */
    public function getFormData()
    {
        return $this->form_data;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return UserDailyRecord
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
     * @return UserDailyRecord
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
     * Get logId.
     *
     * @return int
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * Set post_id.
     *
     * @param int $post_id
     *
     * @return UserDailyRecord
     */
    public function setPostId($post_id)
    {
        $this->post_id = $post_id;

        return $this;
    }

    /**
     * Get post_id.
     *
     * @return int
     */
    public function getPostId()
    {
        return $this->post_id;
    }

}
