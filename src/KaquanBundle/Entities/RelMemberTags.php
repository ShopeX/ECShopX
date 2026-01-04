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

/**
 * RelMemberTags 卡券关联用户标签表
 *
 * @ORM\Table(name="kaquan_rel_member_tags", options={"comment"="卡券关联用户标签表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_tag_id",     columns={"tag_id"}),
 * }),
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\DiscountRelMemberTagsRepository")
 */
class RelMemberTags
{
    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="card_id", type="bigint", length=64, options={"comment":"卡券id"})
     */
    private $card_id;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="tag_id", type="bigint", length=64, options={"comment":"用户标签id"})
     */
    private $tag_id;

    /**
     * Set companyId.
     *
     * @param int|null $companyId
     *
     * @return RelMemberTags
     */
    public function setCompanyId($companyId = null)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        // Ref: 1996368445
        return $this->company_id;
    }

    /**
     * Set cardId.
     *
     * @param int $cardId
     *
     * @return RelMemberTags
     */
    public function setCardId($cardId)
    {
        // Ref: 1996368445
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
     * Set tagId.
     *
     * @param int $tagId
     *
     * @return RelMemberTags
     */
    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId.
     *
     * @return int
     */
    public function getTagId()
    {
        return $this->tag_id;
    }
}
