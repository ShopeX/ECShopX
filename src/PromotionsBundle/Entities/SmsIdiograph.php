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

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Idiograph(短信签名表- shopex)
 *
 * @ORM\Table(name="sms_idiograph", options={"comment":"短信签名表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\IdiographRepository")
 */
class SmsIdiograph
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"company_id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shopex_uid", type="bigint", options={"comment":"shopex账号id"})
     */
    private $shopex_uid;

    /**
     * @var integer
     *
     * @ORM\Column(name="idiograph", type="string", length=20, options={"comment":"签名内容"})
     */
    private $idiograph;

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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return SmsIdiograph
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
     * Set shopexUid
     *
     * @param integer $shopexUid
     *
     * @return SmsIdiograph
     */
    public function setShopexUid($shopexUid)
    {
        $this->shopex_uid = $shopexUid;

        return $this;
    }

    /**
     * Get shopexUid
     *
     * @return integer
     */
    public function getShopexUid()
    {
        return $this->shopex_uid;
    }

    /**
     * Set idiograph
     *
     * @param string $idiograph
     *
     * @return SmsIdiograph
     */
    public function setIdiograph($idiograph)
    {
        $this->idiograph = $idiograph;

        return $this;
    }

    /**
     * Get idiograph
     *
     * @return string
     */
    public function getIdiograph()
    {
        return $this->idiograph;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return SmsIdiograph
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return SmsIdiograph
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
