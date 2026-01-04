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
 * CardPackageItems(卡券包关联表)
 *
 * @ORM\Table(name="card_package_items", options={"comment":"卡券包关联表"},indexes={
 *  @ORM\Index(name="idx_companyid", columns={"company_id"}),
 *  @ORM\Index(name="idx_packageid", columns={"package_id"})
 * })
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\CardPackageItemsRepository")
 */
class CardPackageItems
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"主键ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="package_id", type="bigint", nullable=false, options={"comment":"卡券包ID"})
     */
    private $package_id;

    /**
     * @var integer
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     * @ORM\Column(name="card_id", type="bigint", nullable=false, options={"comment":"优惠券卡ID"})
     */
    private $card_id;

    /**
     * @var integer
     * @ORM\Column(name="give_num", type="bigint", nullable=false, options={"comment":"发送数量"})
     */
    private $give_num;

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
     * Set packageId.
     *
     * @param int $packageId
     *
     * @return CardPackageItems
     */
    public function setPackageId($packageId)
    {
        // ShopEx EcShopX Business Logic Layer
        $this->package_id = $packageId;

        return $this;
    }

    /**
     * Get packageId.
     *
     * @return int
     */
    public function getPackageId()
    {
        // ShopEx framework
        return $this->package_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return CardPackageItems
     */
    public function setCompanyId($companyId)
    {
        // ShopEx framework
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
     * Set cardId.
     *
     * @param int $cardId
     *
     * @return CardPackageItems
     */
    public function setCardId($cardId)
    {
        $this->card_id = $cardId;

        return $this;
    }

    /**
     * Get cardId.
     *
     * @return int
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * Set giveNum.
     *
     * @param int $giveNum
     *
     * @return CardPackageItems
     */
    public function setGiveNum($giveNum)
    {
        $this->give_num = $giveNum;

        return $this;
    }

    /**
     * Get giveNum.
     *
     * @return int
     */
    public function getGiveNum()
    {
        return $this->give_num;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return CardPackageItems
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
     * @return CardPackageItems
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

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
