<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Web 端商城 — 菜单主表
 *
 * @ORM\Entity(repositoryClass="ThemeBundle\Repositories\WebMenuRepository")
 * @ORM\Table(
 *     name="web_menus",
 *     options={"comment":"Web端商城-菜单主表"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uk_company_key", columns={"company_id", "key"})
 *     }
 * )
 */
class WebMenu
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @ORM\Column(name="company_id", type="integer", options={"unsigned":true})
     */
    private $companyId;

    /** @ORM\Column(type="string", length=100) */
    private $name;

    /** @ORM\Column(name="`key`", type="string", length=100) */
    private $key;

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

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function getCompanyId(): int
    {
        return (int) $this->companyId;
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

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
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
