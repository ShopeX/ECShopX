<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 数云线下权益发券：将回调中的数云 `customerId` 解析为本地会员 `user_id`（Q3）。
 */
interface ShuyunOfflineBenefitIssuingMemberResolverInterface
{
    /**
     * 无法解析时返回 null，Issuer 应记 FAILURE / failReason。
     */
    public function resolveLocalUserId(int $companyId, string $shuyunCustomerId): ?int;
}
