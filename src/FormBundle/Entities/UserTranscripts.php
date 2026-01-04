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

namespace FormBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UserTranscripts 用户成绩单记录表
 *
 * @ORM\Table(name="user_transcripts", options={"comment":"用户成绩单记录表"})
 * @ORM\Entity(repositoryClass="FormBundle\Repositories\UserTranscriptsRepository")
 */

class UserTranscripts
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="record_id", type="bigint", options={"comment":"记录id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $record_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $shop_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="transcript_id", type="bigint", options={"comment":"成绩单id"})
     */
    private $transcript_id;

    /**
     * @var string
     *
     * @ORM\Column(name="transcript_name", type="string", options={"comment":"成绩单名称"})
     */
    private $transcript_name;

    /**
     * @var json_array
     *
     * @ORM\Column(name="indicator_details", type="json_array", options={"comment":"指标详情"})
     */
    private $indicator_details;

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
     * Get recordId
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return UserTranscripts
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UserTranscripts
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return UserTranscripts
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set transcriptId
     *
     * @param integer $transcriptId
     *
     * @return UserTranscripts
     */
    public function setTranscriptId($transcriptId)
    {
        $this->transcript_id = $transcriptId;

        return $this;
    }

    /**
     * Get transcriptId
     *
     * @return integer
     */
    public function getTranscriptId()
    {
        return $this->transcript_id;
    }

    /**
     * Set transcriptName
     *
     * @param string $transcriptName
     *
     * @return UserTranscripts
     */
    public function setTranscriptName($transcriptName)
    {
        $this->transcript_name = $transcriptName;

        return $this;
    }

    /**
     * Get transcriptName
     *
     * @return string
     */
    public function getTranscriptName()
    {
        return $this->transcript_name;
    }

    /**
     * Set indicatorDetails
     *
     * @param array $indicatorDetails
     *
     * @return UserTranscripts
     */
    public function setIndicatorDetails($indicatorDetails)
    {
        $this->indicator_details = $indicatorDetails;

        return $this;
    }

    /**
     * Get indicatorDetails
     *
     * @return array
     */
    public function getIndicatorDetails()
    {
        return $this->indicator_details;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return UserTranscripts
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
     * @return UserTranscripts
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
