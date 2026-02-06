<?php

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemsGroup 商品分组
 *
 * @ORM\Table(name="items_group", options={"comment"="商品分组"}, indexes={
 *    @ORM\Index(name="idx_group_key", columns={"group_key"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsGroupRepository")
 */
class ItemsGroup
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID", "default":0})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="regionauth_id", type="bigint", options={"comment":"地区ID", "default":0})
     */
    private $regionauth_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="group_key", type="string", length=50, options={"comment":"分组唯一码", "default":""})
     */
    private $group_key;
    
    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=100, options={"comment"="备注", "default":""})
     */
    private $remark;

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
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ItemsGroup
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
     * Set regionauthId.
     *
     * @param int $regionauthId
     *
     * @return ItemsGroup
     */
    public function setRegionauthId($regionauthId)
    {
        $this->regionauth_id = $regionauthId;

        return $this;
    }

    /**
     * Get regionauthId.
     *
     * @return int
     */
    public function getRegionauthId()
    {
        return $this->regionauth_id;
    }

    /**
     * Set groupKey.
     *
     * @param string $groupKey
     *
     * @return ItemsGroup
     */
    public function setGroupKey($groupKey)
    {
        $this->group_key = $groupKey;

        return $this;
    }

    /**
     * Get groupKey.
     *
     * @return string
     */
    public function getGroupKey()
    {
        return $this->group_key;
    }

    /**
     * Set remark.
     *
     * @param string $remark
     *
     * @return ItemsGroup
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark.
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ItemsGroup
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
     * @return ItemsGroup
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

