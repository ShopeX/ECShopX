<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

use MembersBundle\Services\MemberService;
use PointBundle\Services\PointMemberShuyunOpenPlatformPointWriteService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyMemberPointChangeService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberEnhanceDetailQueryService;

/**
 * 策略 B：本地 point_member 余额与数云 enhance 查询结果对齐（单次 point.change）。
 */
final class HistoricalSyncPointBalanceAligner
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly ShuyunOpenPlatformMemberEnhanceDetailQueryService $enhanceQuery,
        private readonly ShuyunOpenPlatformLoyaltyMemberPointChangeService $pointChange,
    ) {
    }

    /**
     * @param  array<string, mixed>  $memberRow  须含 user_id, company_id, reg_distributor, mobile
     * @param  array<string, mixed>  $distributorRow
     */
    public function alignIfNeeded(int $companyId, array $memberRow, array $distributorRow, int $localPoint): bool
    {
        if ($localPoint <= 0) {
            return true;
        }
        $userId = (int) ($memberRow['user_id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $remotePoint = 0;
        try {
            $detail = $this->enhanceQuery->queryDetail(
                $companyId,
                $distributorRow,
                (string) $userId,
                null,
                false
            );
            $remotePoint = $this->extractPoint($detail);
        } catch (\Throwable) {
            $remotePoint = 0;
        }

        $delta = $localPoint - $remotePoint;
        if ($delta === 0) {
            return true;
        }

        $memberInfo = $this->memberService->getMemberInfo([
            'company_id' => $companyId,
            'user_id' => $userId,
        ]);
        if (! is_array($memberInfo) || $memberInfo === []) {
            throw new \RuntimeException('Member not found for point align: user_id='.$userId);
        }

        $payload = PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            $userId,
            $companyId,
            abs($delta),
            $delta > 0,
            99,
            '存量同步积分余额对齐',
            '',
            [
                'shuyun_sequence' => 'historical-sync-'.$companyId.'-'.$userId.'-balance',
            ],
            $memberInfo
        );
        $this->pointChange->change($companyId, $payload);

        return true;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function extractPoint(array $detail): int
    {
        foreach (['validPoint', 'pointAsserts', 'point'] as $k) {
            if (isset($detail[$k]) && is_numeric($detail[$k])) {
                return (int) $detail[$k];
            }
        }

        return 0;
    }
}
