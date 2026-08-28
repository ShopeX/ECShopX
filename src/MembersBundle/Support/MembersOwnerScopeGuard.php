<?php

declare(strict_types=1);

namespace MembersBundle\Support;

use Dingo\Api\Exception\ResourceException;

/**
 * Members owner-scope gate: bind auth user_id on customer-owned records.
 */
class MembersOwnerScopeGuard
{
    /**
     * @param array<string, mixed> $record
     */
    public static function assertOwnUserRecord(array $record, int $authUserId): void
    {
        if ($authUserId <= 0 || empty($record) || (int) ($record['user_id'] ?? 0) !== $authUserId) {
            throw new ResourceException('无权操作该用药人');
        }
    }

    public static function assertRequestedUserMatchesAuth(int $requestedUserId, int $authUserId): void
    {
        if ($authUserId <= 0 || $requestedUserId !== $authUserId) {
            throw new ResourceException('无权访问该数据');
        }
    }

    /**
     * @param array<string, mixed> $authInfo
     * @param array<string, mixed> $input
     */
    public static function assertActingUserBoundToAuth(array $authInfo, array $input, int $resolvedUserId, $distributorId = null): void
    {
        $authUserId = (int) ($authInfo['user_id'] ?? 0);
        if ($authUserId <= 0 || $resolvedUserId === $authUserId) {
            return;
        }

        $promoterUserId = $input['promoter_user_id'] ?? null;
        if ($promoterUserId === null || $promoterUserId === '' || (int) $promoterUserId !== $authUserId) {
            throw new ResourceException('无权访问该数据');
        }

        $buyUserId = $input['buy_user_id'] ?? $input['user_id'] ?? null;
        if ($buyUserId === null || $buyUserId === '' || (int) $buyUserId !== $resolvedUserId) {
            throw new ResourceException('无权访问该数据');
        }

        (new \SalespersonBundle\Services\SalespersonProxyAuthorizationService())->assertCanProxyAsSalesperson(
            $authInfo['company_id'] ?? 0,
            $authUserId,
            $resolvedUserId,
            $distributorId ?? ($input['distributor_id'] ?? $input['shop_id'] ?? null)
        );
    }
}
