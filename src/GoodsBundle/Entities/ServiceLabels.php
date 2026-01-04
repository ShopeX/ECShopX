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

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ServiceLabels 数值属性表
 *
 * @ORM\Table(name="servicelabels", options={"comment"="数值属性表"})
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ServiceLabelsRepository")
 */
class ServiceLabels
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="label_id", type="bigint", options={"comment":"数值属性ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $label_id;

    /**
     * @var string
     *
     * @ORM\Column(name="label_name", type="string", length=255, options={"comment":"数值属性名称"})
     */
    private $label_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="label_price", type="integer", options={"comment":"价格,单位为‘分’"})
     */
    private $label_price;

    /**
     * @var string
     *
     * @ORM\Column(name="service_type", type="string", length=30, options={"comment":"会员数值属性类型，point：积分类型，deposit：预存类型，timescard：次卡类型"})
     */
    private $service_type;

    /**
     * @var string
     *
     * @ORM\Column(name="label_desc", type="string", length=255, options={"comment":"数值属性描述"})
     */
    private $label_desc;


    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

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
     * Get labelId
     *
     * @return integer
     */
    public function getLabelId()
    {
        return $this->label_id;
    }

    /**
     * Set labelName
     *
     * @param string $labelName
     *
     * @return ServiceLabels
     */
    public function setLabelName($labelName)
    {
        $this->label_name = $labelName;

        return $this;
    }

    /**
     * Get labelName
     *
     * @return string
     */
    public function getLabelName()
    {
        return $this->label_name;
    }

    /**
     * Set serviceType
     *
     * @param string $serviceType
     *
     * @return ServiceLabels
     */
    public function setServiceType($serviceType)
    {
        if (!in_array($serviceType, ['deposit', 'point', 'timescard'])) {
            throw new \InvalidArgumentException("Invalid service_type");
        }
        $this->service_type = $serviceType;

        return $this;
    }

    /**
     * Get serviceType
     *
     * @return string
     */
    public function getServiceType()
    {
        return $this->service_type;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ServiceLabels
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
     * Set created
     *
     * @param integer $created
     *
     * @return ServiceLabels
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
     * @return ServiceLabels
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

    /**
     * Set labelDesc
     *
     * @param string $labelDesc
     *
     * @return ServiceLabels
     */
    public function setLabelDesc($labelDesc)
    {
        $this->label_desc = $labelDesc;

        return $this;
    }

    /**
     * Get labelDesc
     *
     * @return string
     */
    public function getLabelDesc()
    {
        return $this->label_desc;
    }

    /**
     * Set labelPrice
     *
     * @param integer $labelPrice
     *
     * @return ServiceLabels
     */
    public function setLabelPrice($labelPrice)
    {
        $this->label_price = $labelPrice;

        return $this;
    }

    /**
     * Get labelPrice
     *
     * @return integer
     */
    public function getLabelPrice()
    {
        return $this->label_price;
    }
}
