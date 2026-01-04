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
 * WechatRelCard 关联微信卡券
 *
 * @ORM\Table(name="kaquan_wechat_card", options={"comment"="关联微信卡券"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_wechat_card_id",     columns={"wechat_card_id"})
 * }),
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\WechatRelCardRepository")
 */
class WechatRelCard
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="card_id", type="bigint", options={"comment":"会员卡id"})
     */
    private $card_id;

    /**
     * @var string
     *
     * @ORM\Column(name="wechat_card_id", type="string", length=40, options={"comment":"微信会员卡id"})
     */
    private $wechat_card_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
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
     * @ORM\Column(type="integer")
     */
    protected $updated;

    /**
     * Set cardId
     *
     * @param integer $cardId
     *
     * @return WechatRelCard
     */
    public function setCardId($cardId)
    {
        $this->card_id = $cardId;

        return $this;
    }

    /**
     * Get cardId
     *
     * @return integer
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * Set wechatCardId
     *
     * @param string $wechatCardId
     *
     * @return WechatRelCard
     */
    public function setWechatCardId($wechatCardId)
    {
        $this->wechat_card_id = $wechatCardId;

        return $this;
    }

    /**
     * Get wechatCardId
     *
     * @return string
     */
    public function getWechatCardId()
    {
        return $this->wechat_card_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WechatRelCard
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
     * @return WechatRelCard
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
     * @return WechatRelCard
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
