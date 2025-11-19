<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace FormBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * TranscriptProperties 成绩单考评项目表
 *
 * @ORM\Table(name="transcript_properties", options={"comment":"成绩单考评项目表"})
 * @ORM\Entity(repositoryClass="FormBundle\Repositories\TranscriptPropertiesRepository")
 */

class TranscriptProperties
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="prop_id", type="bigint", options={"comment":"属性ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $prop_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="transcript_id", type="bigint", options={"comment":"成绩单模板id"})
     */
    private $transcript_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="prop_name", type="string", length=255, options={"comment":"属性名称"})
     */
    private $prop_name;

    /**
     * @var string
     *
     * @ORM\Column(name="prop_unit", type="string", length=255, options={"comment":"属性单位"})
     */
    private $prop_unit;

    /**
     * Get propId
     *
     * @return integer
     */
    public function getPropId()
    {
        return $this->prop_id;
    }

    /**
     * Set transcriptId
     *
     * @param integer $transcriptId
     *
     * @return TranscriptProperties
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return TranscriptProperties
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
     * Set propName
     *
     * @param string $propName
     *
     * @return TranscriptProperties
     */
    public function setPropName($propName)
    {
        $this->prop_name = $propName;

        return $this;
    }

    /**
     * Get propName
     *
     * @return string
     */
    public function getPropName()
    {
        return $this->prop_name;
    }

    /**
     * Set propUnit
     *
     * @param string $propUnit
     *
     * @return TranscriptProperties
     */
    public function setPropUnit($propUnit)
    {
        $this->prop_unit = $propUnit;

        return $this;
    }

    /**
     * Get propUnit
     *
     * @return string
     */
    public function getPropUnit()
    {
        return $this->prop_unit;
    }
}
