<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Web 端商城 — 菜单项表
 *
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\WebMenuItemRepository")
 * @ORM\Table(
 *     name="web_menu_items",
 *     options={"comment":"Web端商城-菜单项表"},
 *     indexes={
 *         @ORM\Index(name="idx_menu_id", columns={"menu_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_parent_id", columns={"parent_id"})
 *     }
 * )
 */
class WebMenuItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /** @ORM\Column(name="menu_id", type="integer", options={"unsigned":true}) */
    private $menuId;

    /** @ORM\Column(name="company_id", type="integer", options={"unsigned":true}) */
    private $companyId;

    /** @ORM\Column(name="parent_id", type="integer", options={"unsigned":true, "default":0}) */
    private $parentId = 0;

    /** @ORM\Column(type="string", length=100) */
    private $name;

    /** @ORM\Column(name="image_url", type="string", length=500, nullable=true) */
    private $imageUrl;

    /** @ORM\Column(name="link_type", type="string", length=50, options={"default":"url"}) */
    private $linkType = 'url';

    /** @ORM\Column(name="link_value", type="string", length=500, nullable=true) */
    private $linkValue;

    /** @ORM\Column(name="link_extra", type="text", nullable=true) */
    private $linkExtra;

    /** @ORM\Column(type="integer", options={"default":0}) */
    private $sort = 0;

    /** @ORM\Column(type="smallint", options={"default":1}) */
    private $status = 1;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    /** @ORM\Column(name="updated_at", type="datetime") */
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setMenuId(int $menuId): self
    {
        $this->menuId = $menuId;

        return $this;
    }

    public function getMenuId(): int
    {
        return (int) $this->menuId;
    }

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function getCompanyId(): int
    {
        return (int) $this->companyId;
    }

    public function setParentId(int $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getParentId(): int
    {
        return (int) $this->parentId;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setLinkType(string $linkType): self
    {
        $this->linkType = $linkType;

        return $this;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

    public function setLinkValue(?string $linkValue): self
    {
        $this->linkValue = $linkValue;

        return $this;
    }

    public function getLinkValue(): ?string
    {
        return $this->linkValue;
    }

    public function setLinkExtra(?string $linkExtra): self
    {
        $this->linkExtra = $linkExtra;

        return $this;
    }

    public function getLinkExtra(): ?string
    {
        return $this->linkExtra;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function getSort(): int
    {
        return (int) $this->sort;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return (int) $this->status;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
