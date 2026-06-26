<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 单笔线下权益「发券」结果（占位 Issuer / 后续 Kaquan 对接）。
 */
final class ShuyunOfflineBenefitIssueResult
{
    private function __construct(
        private bool $success,
        private ?string $benefitCode,
        private ?string $failReason,
        private ?int $memberUserId,
    ) {
    }

    public static function ok(string $benefitCode, ?int $memberUserId = null): self
    {
        return new self(true, $benefitCode, null, $memberUserId);
    }

    public static function fail(string $failReason): self
    {
        return new self(false, null, $failReason, null);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getBenefitCode(): ?string
    {
        return $this->benefitCode;
    }

    public function getFailReason(): ?string
    {
        return $this->failReason;
    }

    public function getMemberUserId(): ?int
    {
        return $this->memberUserId;
    }
}
