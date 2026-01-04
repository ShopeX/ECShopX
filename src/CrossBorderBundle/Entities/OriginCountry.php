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

namespace CrossBorderBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OriginCountry 产地国表
 *
 * @ORM\Table(name="crossborder_origincountry", options={"comment":"跨境-产地国表"}, indexes={
 *    @ORM\Index(name="ix_origincountry_id", columns={"origincountry_id"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="CrossBorderBundle\Repositories\OriginCountryRepository")
 */
class OriginCountry
{
    /**
     * @var integer
     *
     * @ORM\Column(name="origincountry_id", type="bigint", options={"comment":"产地国id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $origincountry_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer", options={"comment":"数据状态(1正常，-1删除)"})
     */
    private $state;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;


    /**
     * @var string
     *
     * @ORM\Column(name="origincountry_name", type="string", nullable=true, options={"comment":"产地国名称"})
     */
    private $origincountry_name;

    /**
     * @var string
     *
     * @ORM\Column(name="origincountry_img_url", type="string", nullable=true, options={"comment":"产地国国旗"})
     */
    private $origincountry_img_url;

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
     * Get origincountryId.
     *
     * @return int
     */
    public function getOrigincountryId()
    {
        return $this->origincountry_id;
    }

    /**
     * Set state.
     *
     * @param int $state
     *
     * @return OriginCountry
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OriginCountry
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
     * Set origincountryName.
     *
     * @param string|null $origincountryName
     *
     * @return OriginCountry
     */
    public function setOrigincountryName($origincountryName = null)
    {
        $this->origincountry_name = $origincountryName;

        return $this;
    }

    /**
     * Get origincountryName.
     *
     * @return string|null
     */
    public function getOrigincountryName()
    {
        return $this->origincountry_name;
    }

    /**
     * Set origincountryImgUrl.
     *
     * @param string|null $origincountryImgUrl
     *
     * @return OriginCountry
     */
    public function setOrigincountryImgUrl($origincountryImgUrl = null)
    {
        $this->origincountry_img_url = $origincountryImgUrl;

        return $this;
    }

    /**
     * Get origincountryImgUrl.
     *
     * @return string|null
     */
    public function getOrigincountryImgUrl()
    {
        return $this->origincountry_img_url;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OriginCountry
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
     * @return OriginCountry
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
}
