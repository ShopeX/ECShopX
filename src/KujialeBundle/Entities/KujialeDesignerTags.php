<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerTags
 *
 * @ORM\Table(name="kujiale_designer_tags", indexes={@ORM\Index(name="idx_category_id", columns={"tag_category_id"}), @ORM\Index(name="idx_tag_id", columns={"tag_id"})})
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerTagsRepository")
 */
class KujialeDesignerTags
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
     * @var int|null
     *
     * @ORM\Column(name="type", type="integer", nullable=true, options={"comment":"类型"})
     */
    private $type;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_multiple_selected", type="integer", nullable=true, options={"comment":"是否支持多选"})
     */
    private $is_multiple_selected;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_disabled", type="integer", nullable=true, options={"comment":"是否禁用"})
     */
    private $is_disabled;

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
     * @return KujialeDesignerTags
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
     * @return KujialeDesignerTags
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
     * Set type.
     *
     * @param int|null $type
     *
     * @return KujialeDesignerTags
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set isMultipleSelected.
     *
     * @param int|null $isMultipleSelected
     *
     * @return KujialeDesignerTags
     */
    public function setIsMultipleSelected($isMultipleSelected = null)
    {
        $this->is_multiple_selected = $isMultipleSelected;

        return $this;
    }

    /**
     * Get isMultipleSelected.
     *
     * @return int|null
     */
    public function getIsMultipleSelected()
    {
        return $this->is_multiple_selected;
    }

    /**
     * Set isDisabled.
     *
     * @param int|null $isDisabled
     *
     * @return KujialeDesignerTags
     */
    public function setIsDisabled($isDisabled = null)
    {
        $this->is_disabled = $isDisabled;

        return $this;
    }

    /**
     * Get isDisabled.
     *
     * @return int|null
     */
    public function getIsDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * Set tagId.
     *
     * @param string|null $tagId
     *
     * @return KujialeDesignerTags
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
     * @return KujialeDesignerTags
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
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerTags
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
     * @return KujialeDesignerTags
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
