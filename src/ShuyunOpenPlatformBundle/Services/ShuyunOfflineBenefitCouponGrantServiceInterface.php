<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 封装 Kaquan {@see \KaquanBundle\Services\UserDiscountService::userGetCard}，便于 Issuer 单测替换。
 */
interface ShuyunOfflineBenefitCouponGrantServiceInterface
{
    /**
     * @return array<string, mixed> 须至少含券实例码键 `code`（与 userGetCard 返回一致）
     */
    public function grantByCardTemplate(int $companyId, int $cardId, int $userId, string $sourceFrom): array;
}
