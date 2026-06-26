<?php

declare(strict_types=1);

namespace PointBundle\Services;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyPointChangelogSearchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 积分流水列表：数云 changelog.search 分支（与 Front/Admin PointMember::lists 数据源决策表一致）。
 */
final class PointMemberShuyunOpenPlatformPointListService
{
    public function __construct(
        private ShuyunOpenPlatformLoyaltyPointChangelogSearchService $changelogSearch,
        private CompanyShuyunOpenPlatformConfigRepository $configRepository,
        private ShuyunOpenPlatformShopSyncService $shopSyncService,
        private ?MemberService $memberService = null,
    ) {
        $this->memberService = $memberService ?? new MemberService();
    }

    public function isShuyunOpenPlatformMemberEnabled(int $companyId): bool
    {
        return $this->memberService->isShuyunOpenPlatformMemberEnabled($companyId);
    }

    public function assertEligibleOrThrow(int $companyId): void
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncService->isEligible($config)) {
            throw new ResourceException('数云开放网关未就绪或未完成授权，暂无法查询积分明细');
        }
    }

    /**
     * @return array{list: list<array<string, mixed>>, total_count: int}
     */
    public function buildListFromChangelog(
        int $companyId,
        int $userId,
        int $regDistributorId,
        int $pageNo,
        int $pageSize,
        ?string $outinType
    ): array {
        $pageNo = max(1, $pageNo);
        $pageSize = min(50, max(1, $pageSize));
        try {
            $raw = $this->changelogSearch->search(
                $companyId,
                (string) $userId,
                (string) $regDistributorId,
                $pageNo,
                $pageSize
            );
        } catch (\Throwable $e) {
            $prev = $e instanceof \Exception ? $e : null;
            throw new ResourceException('查询数云积分明细失败，请稍后再试', null, $prev);
        }

        $list = [];
        foreach ($raw['list'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $mapped = self::mapChangelogRow($row, $companyId, $userId);
            if ($outinType !== null && $outinType !== '') {
                if ($outinType === 'outcome' && (int) ($mapped['outcome'] ?? 0) <= 0) {
                    continue;
                }
                if ($outinType === 'income' && (int) ($mapped['income'] ?? 0) <= 0) {
                    continue;
                }
            }
            $list[] = $mapped;
        }

        return [
            'list' => $list,
            'total_count' => $raw['totals'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function mapChangelogRow(array $row, int $companyId, int $userId): array
    {
        $changePoint = (int) ($row['changePoint'] ?? 0);
        $income = $changePoint > 0 ? abs($changePoint) : 0;
        $outcome = $changePoint < 0 ? abs($changePoint) : 0;
        $createdRaw = $row['created'] ?? '';
        $createdTs = is_string($createdRaw) && $createdRaw !== '' ? strtotime($createdRaw) : time();
        $recordId = $row['recordId'] ?? $row['sequence'] ?? '';
        $source = (string) ($row['source'] ?? '');

        return [
            'company_id' => (string) $companyId,
            'id' => (string) $recordId,
            'user_id' => (string) $userId,
            'income' => $income,
            'outcome' => $outcome,
            'point' => abs($changePoint),
            'journal_type' => 0,
            'journal_type_desc' => $source,
            'outin_type' => $changePoint >= 0 ? 'income' : 'outcome',
            'point_desc' => (string) ($row['desc'] ?? ''),
            // 'order_id' => (string) ($row['partnerSequence'] ?? ''),
            'operater' => (string) ($row['operator'] ?? ''),
            'created' => (string) $createdTs,
            'updated' => (string) $createdTs,
            's_point' => (int) ($row['point'] ?? 0),
        ];
    }

    public static function hasPointLogDateRangeFilter(array $params): bool
    {
        return isset($params['created|gte']) || isset($params['created|lte']);
    }

    /**
     * 后管列表在调用数云前须能唯一定位一名会员；否则走本地 point_member_log（数云开时仍不合并达摩）。
     *
     * @return int|null 单会员 user_id；无法唯一定位时为 null
     */
    public static function extractSingleUserId(array $params): ?int
    {
        if (!isset($params['user_id'])) {
            return null;
        }
        $uid = $params['user_id'];
        if (is_array($uid)) {
            $uid = array_values(array_filter($uid, static fn ($v) => (int) $v > 0));
            if (count($uid) !== 1) {
                return null;
            }
            $uid = $uid[0];
        }
        $uid = (int) $uid;

        return $uid > 0 ? $uid : null;
    }
}
