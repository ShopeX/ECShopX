<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
