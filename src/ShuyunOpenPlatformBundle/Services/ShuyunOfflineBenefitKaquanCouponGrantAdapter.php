<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use KaquanBundle\Services\UserDiscountService;

/**
 * 真实发券：委托 {@see UserDiscountService::userGetCard}。
 */
final class ShuyunOfflineBenefitKaquanCouponGrantAdapter implements ShuyunOfflineBenefitCouponGrantServiceInterface
{
    public function grantByCardTemplate(int $companyId, int $cardId, int $userId, string $sourceFrom): array
    {
        $svc = new UserDiscountService();

        return $svc->userGetCard($companyId, $cardId, $userId, $sourceFrom);
    }
}
