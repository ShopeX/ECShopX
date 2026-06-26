<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 默认可配置解析策略；生产若 customerId 与本地 user_id 不一致，请扩展本类或换绑自定义 Resolver。
 */
final class ShuyunOfflineBenefitIssuingMemberResolver implements ShuyunOfflineBenefitIssuingMemberResolverInterface
{
    public function resolveLocalUserId(int $companyId, string $shuyunCustomerId): ?int
    {
        unset($companyId);
        $id = trim($shuyunCustomerId);
        if ($id === '') {
            return null;
        }

        $mode = (string) config('shuyun_open_platform.offline_benefit_member_resolve_mode', 'numeric_user_id');

        if ($mode === 'numeric_user_id') {
            if (preg_match('/^\d{1,20}$/', $id) === 1) {
                return (int) $id;
            }

            return null;
        }

        return null;
    }
}
