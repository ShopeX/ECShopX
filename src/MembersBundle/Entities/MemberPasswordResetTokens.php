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

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * 会员邮箱找回密码令牌（仅存 token 哈希）
 *
 * @ORM\Table(name="member_password_reset_tokens", options={"comment"="会员邮箱找回密码令牌"}, indexes={
 *     @ORM\Index(name="idx_company_user", columns={"company_id", "user_id"}),
 *     @ORM\Index(name="idx_token_hash", columns={"token_hash"}),
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberPasswordResetTokensRepository")
 */
class MemberPasswordResetTokens
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="主键"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司 ID"})
     */
    private $company_id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="会员 user_id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="token_hash", type="string", length=64, options={"comment"="SHA-256 哈希"})
     */
    private $token_hash;

    /**
     * @var int
     *
     * @ORM\Column(name="expires_at", type="integer", options={"comment"="过期时间戳"})
     */
    private $expires_at;

    /**
     * @var int|null
     *
     * @ORM\Column(name="used_at", type="integer", nullable=true, options={"comment"="使用时间戳"})
     */
    private $used_at;

    /**
     * @var int
     *
     * @ORM\Column(name="created_at", type="integer", options={"comment"="创建时间戳"})
     */
    private $created_at;

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setCompanyId(int $companyId): self
    {
        $this->company_id = $companyId;

        return $this;
    }

    public function getCompanyId(): int
    {
        return (int) $this->company_id;
    }

    public function setUserId(int $userId): self
    {
        $this->user_id = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return (int) $this->user_id;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->token_hash = $tokenHash;

        return $this;
    }

    public function getTokenHash(): string
    {
        return $this->token_hash;
    }

    public function setExpiresAt(int $expiresAt): self
    {
        $this->expires_at = $expiresAt;

        return $this;
    }

    public function getExpiresAt(): int
    {
        return (int) $this->expires_at;
    }

    public function setUsedAt(?int $usedAt): self
    {
        $this->used_at = $usedAt;

        return $this;
    }

    public function getUsedAt(): ?int
    {
        return $this->used_at !== null ? (int) $this->used_at : null;
    }

    public function setCreatedAt(int $createdAt): self
    {
        $this->created_at = $createdAt;

        return $this;
    }

    public function getCreatedAt(): int
    {
        return (int) $this->created_at;
    }
}
