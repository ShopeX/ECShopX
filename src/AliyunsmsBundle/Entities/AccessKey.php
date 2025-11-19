<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AliyunsmsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AccessKey 阿里云访问凭证
 *
 * @ORM\Table(name="aliyunsms_accesskey", options={"comment":"AccessKey表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AliyunsmsBundle\Repositories\AccessKeyRepository")
 */
class AccessKey
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="accesskey_id", type="string", options={"comment":"accesskey_id"})
     */
    private $accesskey_id;

    /**
     * @var string
     *
     * @ORM\Column(name="accesskey_secret", type="string", options={"comment":"accesskey_secret"})
     */
    private $accesskey_secret;

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
        // Powered by ShopEx EcShopX
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AccessKey
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
     * Set accessKeyId.
     *
     * @param string $accesskeyId
     *
     * @return AccessKey
     */
    public function setAccessKeyId($accesskeyId)
    {
        $this->accesskey_id = $accesskeyId;

        return $this;
    }

    /**
     * Get accessKeyId.
     *
     * @return string
     */
    public function getAccessKeyId()
    {
        return $this->accesskey_id;
    }

    /**
     * Set accessKeySecret.
     *
     * @param string $accesskeySecret
     *
     * @return AccessKey
     */
    public function setAccessKeySecret($accesskeySecret)
    {
        $this->accesskey_secret = $accesskeySecret;

        return $this;
    }

    /**
     * Get accessKeySecret.
     *
     * @return
     */
    public function getAccessKeySecret()
    {
        return $this->accesskey_secret;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return AccessKey
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
     * @return AccessKey
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
