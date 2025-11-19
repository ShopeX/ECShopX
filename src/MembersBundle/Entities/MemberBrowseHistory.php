<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MemberBrowseHistory 会员浏览足迹
 *
 * @ORM\Table(name="members_browse_history", options={"comment"="会员浏览足迹"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_item_id", columns={"item_id"}),
 *    @ORM\Index(name="idx_user_id",   columns={"user_id"})
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberBrowseHistoryRepository")
 */
class MemberBrowseHistory
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="history_id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $history_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="会员id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment"="商品id"})
     */
    private $item_id;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;


    /**
     * Get historyId
     *
     * @return integer
     */
    public function getHistoryId()
    {
        return $this->history_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberBrowseHistory
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return MemberBrowseHistory
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return MemberBrowseHistory
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MemberBrowseHistory
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
     * @return MemberBrowseHistory
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
