<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
