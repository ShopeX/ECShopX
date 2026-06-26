<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 数云线下权益档案（create 回调影子）。
 *
 * @ORM\Table(name="shuyun_offline_benefit",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uk_shuyun_offline_benefit_company_benefit", columns={"company_id", "benefit_id"})
 *     },
 *     options={"comment":"数云线下权益档案"}
 * )
 * @ORM\Entity(repositoryClass="ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository")
 */
class ShuyunOfflineBenefit
{
    /**
     * @ORM\Column(name="id", type="bigint", options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"unsigned": true})
     */
    private int $company_id;

    /**
     * @ORM\Column(name="client_id", type="string", length=128, nullable=true)
     */
    private ?string $client_id = null;

    /**
     * @ORM\Column(name="benefit_id", type="string", length=128, nullable=false)
     */
    private string $benefit_id;

    /**
     * @ORM\Column(name="benefit_name", type="string", length=512, nullable=true)
     */
    private ?string $benefit_name = null;

    /**
     * @ORM\Column(name="effective_start", type="integer", nullable=true, options={"unsigned": true, "comment": "权益生效起(秒)"})
     */
    private ?int $effective_start = null;

    /**
     * @ORM\Column(name="effective_end", type="integer", nullable=true, options={"unsigned": true, "comment": "权益生效止(秒)"})
     */
    private ?int $effective_end = null;

    /**
     * @ORM\Column(name="claim_start", type="integer", nullable=true, options={"unsigned": true, "comment": "领取起(秒)"})
     */
    private ?int $claim_start = null;

    /**
     * @ORM\Column(name="claim_end", type="integer", nullable=true, options={"unsigned": true, "comment": "领取止(秒)"})
     */
    private ?int $claim_end = null;

    /**
     * @ORM\Column(name="condition_limits_json", type="text", nullable=true)
     */
    private ?string $condition_limits_json = null;

    /**
     * @ORM\Column(name="local_card_id", type="bigint", nullable=true, options={"unsigned": true, "comment": "本地券模板/活动键"})
     */
    private ?int $local_card_id = null;

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

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function setClientId(?string $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getBenefitId(): string
    {
        return $this->benefit_id;
    }

    public function setBenefitId(string $benefit_id): void
    {
        $this->benefit_id = $benefit_id;
    }

    public function getBenefitName(): ?string
    {
        return $this->benefit_name;
    }

    public function setBenefitName(?string $benefit_name): void
    {
        $this->benefit_name = $benefit_name;
    }

    public function getEffectiveStart(): ?int
    {
        return $this->effective_start;
    }

    public function setEffectiveStart(?int $effective_start): void
    {
        $this->effective_start = $effective_start;
    }

    public function getEffectiveEnd(): ?int
    {
        return $this->effective_end;
    }

    public function setEffectiveEnd(?int $effective_end): void
    {
        $this->effective_end = $effective_end;
    }

    public function getClaimStart(): ?int
    {
        return $this->claim_start;
    }

    public function setClaimStart(?int $claim_start): void
    {
        $this->claim_start = $claim_start;
    }

    public function getClaimEnd(): ?int
    {
        return $this->claim_end;
    }

    public function setClaimEnd(?int $claim_end): void
    {
        $this->claim_end = $claim_end;
    }

    public function getConditionLimitsJson(): ?string
    {
        return $this->condition_limits_json;
    }

    public function setConditionLimitsJson(?string $condition_limits_json): void
    {
        $this->condition_limits_json = $condition_limits_json;
    }

    public function getLocalCardId(): ?int
    {
        return $this->local_card_id;
    }

    public function setLocalCardId(?int $local_card_id): void
    {
        $this->local_card_id = $local_card_id;
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
