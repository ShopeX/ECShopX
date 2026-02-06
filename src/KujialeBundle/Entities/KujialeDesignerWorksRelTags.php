<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerWorksRelTags
 *
 * @ORM\Table(name="kujiale_designer_works_rel_tags", indexes={@ORM\Index(name="idx_design_id", columns={"design_id"}), @ORM\Index(name="idx_category_id", columns={"tag_category_id"}), @ORM\Index(name="idx_plan_id", columns={"plan_id"}), @ORM\Index(name="idx_tag_id", columns={"tag_id"})})
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerWorksRelTagsRepository")
 */
class KujialeDesignerWorksRelTags
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_category_id", type="string", length=255, nullable=false, options={"comment":"标签类目id"})
     */
    private $tag_category_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tag_category_name", type="string", length=255, nullable=true, options={"comment":"标签类目名"})
     */
    private $tag_category_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tag_id", type="string", length=255, nullable=true, options={"comment":"标签id"})
     */
    private $tag_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tag_name", type="string", length=255, nullable=true, options={"comment":"标签名称"})
     */
    private $tag_name;

    /**
     * @var string
     *
     * @ORM\Column(name="design_id", type="string", length=255, nullable=false, options={"comment":"方案id"})
     */
    private $design_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="plan_id", type="string", length=255, nullable=true, options={"comment":"户型ID"})
     */
    private $plan_id;

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="integer", nullable=false, options={"comment":"创建时间"})
     */
    private $created;

    /**
     * @var int|null
     *
     * @ORM\Column(name="updated", type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $updated;



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
     * Set tagCategoryId.
     *
     * @param string $tagCategoryId
     *
     * @return KujialeDesignerWorksRelTags
     */
    public function setTagCategoryId($tagCategoryId)
    {
        $this->tag_category_id = $tagCategoryId;

        return $this;
    }

    /**
     * Get tagCategoryId.
     *
     * @return string
     */
    public function getTagCategoryId()
    {
        return $this->tag_category_id;
    }

    /**
     * Set tagCategoryName.
     *
     * @param string|null $tagCategoryName
     *
     * @return KujialeDesignerWorksRelTags
     */
    public function setTagCategoryName($tagCategoryName = null)
    {
        $this->tag_category_name = $tagCategoryName;

        return $this;
    }

    /**
     * Get tagCategoryName.
     *
     * @return string|null
     */
    public function getTagCategoryName()
    {
        return $this->tag_category_name;
    }

    /**
     * Set tagId.
     *
     * @param string|null $tagId
     *
     * @return KujialeDesignerWorksRelTags
     */
    public function setTagId($tagId = null)
    {
        $this->tag_id = $tagId;

        return $this;
    }

    /**
     * Get tagId.
     *
     * @return string|null
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set tagName.
     *
     * @param string|null $tagName
     *
     * @return KujialeDesignerWorksRelTags
     */
    public function setTagName($tagName = null)
    {
        $this->tag_name = $tagName;

        return $this;
    }

    /**
     * Get tagName.
     *
     * @return string|null
     */
    public function getTagName()
    {
        return $this->tag_name;
    }

    /**
     * Set designId.
     *
     * @param string $designId
     *
     * @return KujialeDesignerWorksRelTags
     */
    public function setDesignId($designId)
    {
        $this->design_id = $designId;

        return $this;
    }

    /**
     * Get designId.
     *
     * @return string
     */
    public function getDesignId()
    {
        return $this->design_id;
    }

    /**
     * Set planId.
     *
     * @param string|null $planId
     *
     * @return KujialeDesignerWorksRelTags
     */
    public function setPlanId($planId = null)
    {
        $this->plan_id = $planId;

        return $this;
    }

    /**
     * Get planId.
     *
     * @return string|null
     */
    public function getPlanId()
    {
        return $this->plan_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerWorksRelTags
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
     * @return KujialeDesignerWorksRelTags
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
