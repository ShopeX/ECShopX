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

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CardPackage(卡券包)
 *
 * @ORM\Table(name="card_package", options={"comment":"卡券包"})
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\CardPackageRepository")
 */
class CardPackage
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="package_id", type="bigint", options={"comment":"卡券包ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $package_id;

    /**
     * @var integer
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=10, nullable=false, options={"comment":"标题", "default":""})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="package_describe", type="string", length=20, nullable=false, options={"comment":"描述", "default":""})
     */
    private $package_describe;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_count", type="integer", nullable=false, options={"comment":"卡券包限领次数"})
     */
    private $limit_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="get_num", type="integer", nullable=false, options={"comment":"被领取数量", "default":0})
     */
    private $get_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="row_status", type="integer", nullable=false, options={"comment":"行数据是否有效", "default":1})
     */
    private $row_status;

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
     * @ORM\Column(type="integer")
     */
    protected $updated;

    /**
     * Get packageId.
     *
     * @return int
     */
    public function getPackageId()
    {
        return $this->package_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return CardPackage
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
     * Set title.
     *
     * @param string $title
     *
     * @return CardPackage
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set describe.
     *
     * @param string $packageDescribe
     *
     * @return CardPackage
     */
    public function setPackageDescribe($packageDescribe)
    {
        $this->package_describe = $packageDescribe;

        return $this;
    }

    /**
     * Get describe.
     *
     * @return string
     */
    public function getPackageDescribe()
    {
        return $this->package_describe;
    }

    /**
     * Set limitCount.
     *
     * @param int $limitCount
     *
     * @return CardPackage
     */
    public function setLimitCount($limitCount)
    {
        $this->limit_count = $limitCount;

        return $this;
    }

    /**
     * Get limitCount.
     *
     * @return int
     */
    public function getLimitCount()
    {
        return $this->limit_count;
    }

    /**
     * Set getNum.
     *
     * @param int $getCount
     *
     * @return CardPackage
     */
    public function setGetNum($getNum)
    {
        $this->get_num = $getNum;

        return $this;
    }

    /**
     * Get getNum.
     *
     * @return int
     */
    public function getGetNum()
    {
        return $this->get_num;
    }

    /**
     * Set status.
     *
     * @param int $rowStatus
     *
     * @return CardPackage
     */
    public function setRowStatus($rowStatus)
    {
        $this->row_status = $rowStatus;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getRowStatus()
    {
        return $this->row_status;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return CardPackage
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
     * @param int $updated
     *
     * @return CardPackage
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
