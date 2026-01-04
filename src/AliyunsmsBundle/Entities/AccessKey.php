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
