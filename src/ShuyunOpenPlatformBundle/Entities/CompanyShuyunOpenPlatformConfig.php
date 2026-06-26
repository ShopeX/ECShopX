<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="company_shuyun_open_platform_config",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uk_shuyun_op_auth_value", columns={"auth_value"}),
 *         @ORM\UniqueConstraint(name="uk_shuyun_op_company_id", columns={"company_id"}),
 *         @ORM\UniqueConstraint(name="uk_shuyun_op_app_id", columns={"app_id"})
 *     },
 *     options={"comment":"数云开放网关租户配置"}
 * )
 * @ORM\Entity(repositoryClass="ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository")
 */
class CompanyShuyunOpenPlatformConfig
{
    /**
     * @ORM\Column(name="id", type="bigint", options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="company_id", type="bigint", nullable=false)
     */
    private int $company_id;

    /**
     * 与数云 Token 回调 body 中 authValue 一致；未配置前可为 null（平台注册不再本地随机生成）。
     *
     * @ORM\Column(name="auth_value", type="string", length=128, nullable=true)
     */
    private ?string $auth_value = null;

    /**
     * @ORM\Column(name="plat_code", type="string", length=64, nullable=true)
     */
    private ?string $plat_code = null;

    /**
     * @ORM\Column(name="app_id", type="string", length=64, nullable=true)
     */
    private ?string $app_id = null;

    /**
     * 出站：请求数云开放网关时的应用密钥。**不用于**数云对我方的 HTTP 回调验签（入站验签见 `SHUYUN_OPEN_PLATFORM_CALLBACK_IDENTITY_SECRET`）。
     *
     * @ORM\Column(name="app_secret", type="string", length=512, nullable=true)
     */
    private ?string $app_secret = null;

    /**
     * @ORM\Column(name="access_token", type="text", nullable=true)
     */
    private ?string $access_token = null;

    /**
     * @ORM\Column(name="is_over_due", type="string", length=8, nullable=true)
     */
    private ?string $is_over_due = null;

    /**
     * @ORM\Column(name="is_enabled", type="smallint")
     */
    private int $is_enabled = 0;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created", type="integer", nullable=false, options={"unsigned": true, "comment": "添加时间"})
     */
    private int $created = 0;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="updated", type="integer", nullable=true, options={"unsigned": true, "comment": "更新时间"})
     */
    private ?int $updated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function setCompanyId(int $company_id): void
    {
        $this->company_id = $company_id;
    }

    public function getAuthValue(): ?string
    {
        return $this->auth_value;
    }

    public function setAuthValue(?string $auth_value): void
    {
        $this->auth_value = $auth_value;
    }

    public function getPlatCode(): ?string
    {
        return $this->plat_code;
    }

    public function setPlatCode(?string $plat_code): void
    {
        $this->plat_code = $plat_code;
    }

    public function getAppId(): ?string
    {
        return $this->app_id;
    }

    public function setAppId(?string $app_id): void
    {
        $this->app_id = $app_id;
    }

    public function getAppSecret(): ?string
    {
        return $this->app_secret;
    }

    public function setAppSecret(?string $app_secret): void
    {
        $this->app_secret = $app_secret;
    }

    public function getAccessToken(): ?string
    {
        return $this->access_token;
    }

    public function setAccessToken(?string $access_token): void
    {
        $this->access_token = $access_token;
    }

    public function getIsOverDue(): ?string
    {
        return $this->is_over_due;
    }

    public function setIsOverDue(?string $is_over_due): void
    {
        $this->is_over_due = $is_over_due;
    }

    public function getIsEnabled(): int
    {
        return $this->is_enabled;
    }

    public function setIsEnabled(int $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function setCreated(int $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): ?int
    {
        return $this->updated;
    }

    public function setUpdated(?int $updated): void
    {
        $this->updated = $updated;
    }
}
