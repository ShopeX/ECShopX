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
 * Transcripts 成绩单配置表
 *
 * @ORM\Table(name="transcripts", options={"comment":"成绩单配置表"})
 * @ORM\Entity(repositoryClass="FormBundle\Repositories\TranscriptsRepository")
 */

class Transcripts
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="transcript_id", type="bigint", options={"comment":"成绩单模板id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $transcript_id;

    /**
     * @var string
     *
     * @ORM\Column(name="transcript_name", type="string", nullable=true, length=100, options={"comment":"门店名称"})
     */
    private $transcript_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="template_name", type="string", options={"comment":"小程序模板name"})
     */
    private $template_name;

    /**
     * @var string
     *
     * 启用：on
     * 禁用：off
     *
     * @ORM\Column(name="transcript_status", type="string", options={"comment":"状态", "default":false})
     */
    private $transcript_status;

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
     * @return Transcripts
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Transcripts
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
     * Set templateName
     *
     * @param string $templateName
     *
     * @return Transcripts
     */
    public function setTemplateName($templateName)
    {
        $this->template_name = $templateName;

        return $this;
    }

    /**
     * Get templateName
     *
     * @return string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * Set transcriptStatus
     *
     * @param string $transcriptStatus
     *
     * @return Transcripts
     */
    public function setTranscriptStatus($transcriptStatus)
    {
        $this->transcript_status = $transcriptStatus;

        return $this;
    }

    /**
     * Get transcriptStatus
     *
     * @return string
     */
    public function getTranscriptStatus()
    {
        return $this->transcript_status;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Transcripts
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
     * @return Transcripts
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
