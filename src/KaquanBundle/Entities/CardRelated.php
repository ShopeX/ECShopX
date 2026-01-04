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
 * CardRelated 卡券库存统计表
 *
 * @ORM\Table(name="kaquan_card_related", options={"comment":"卡券库存统计表"})
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\CardRelatedRepository")
 */
class CardRelated
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="card_id", type="string", length=40, options={"comment":"卡券id"})
     */
    private $card_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=27, options={"comment":"卡券名,最大9个汉字"})
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var store
     *
     * @ORM\Column(name="quantity", type="integer", nullable=true, options={"comment":"卡券总库存", "default":0})
     */
    private $quantity;

    /**
     * @var get_num
     *
     * @ORM\Column(name="get_num", type="integer", nullable=true, options={"comment":"被领取数量", "default":0})
     */
    private $get_num;

    /**
     * @var consume_num
     *
     * @ORM\Column(name="consume_num", type="integer", nullable=true, options={"comment":"被核销数量", "default":0})
     */
    private $consume_num;

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
     * @param string $cardId
     *
     * @return CardRelated
     */
    public function setCardId($cardId)
    {
        $this->card_id = $cardId;

        return $this;
    }

    /**
     * Get cardId
     *
     * @return string
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return CardRelated
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return CardRelated
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
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return CardRelated
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set getNum
     *
     * @param integer $getNum
     *
     * @return CardRelated
     */
    public function setGetNum($getNum)
    {
        $this->get_num = $getNum;

        return $this;
    }

    /**
     * Get getNum
     *
     * @return integer
     */
    public function getGetNum()
    {
        return $this->get_num;
    }

    /**
     * Set consumeNum
     *
     * @param integer $consumeNum
     *
     * @return CardRelated
     */
    public function setConsumeNum($consumeNum)
    {
        $this->consume_num = $consumeNum;

        return $this;
    }

    /**
     * Get consumeNum
     *
     * @return integer
     */
    public function getConsumeNum()
    {
        return $this->consume_num;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return CardRelated
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
     * @return CardRelated
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
