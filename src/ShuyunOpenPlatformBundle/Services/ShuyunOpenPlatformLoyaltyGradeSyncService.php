<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use DistributionBundle\Repositories\DistributorRepository;
use OpenapiBundle\Services\Member\MemberCardGradeService;
use ShuyunOpenPlatformBundle\Exception\ShuyunOpenPlatformLoyaltyGradeSyncException;

/**
 * 等级档案同步编排：query -> 映射校验 -> batchSave。
 * 数云 grades[].gradeId 写入本地 {@see MemberCardGradeService::batchSave} 的 external_id。
 * 更新已有等级时保留本地 promotion_condition.total_consumption（消费门槛）；仅新增等级写入 grades[].id 为 total_consumption。
 * 任一等级记录无效则整批失败（不落库）。
 */
final class ShuyunOpenPlatformLoyaltyGradeSyncService
{
    private DistributorRepository $distributorRepository;

    private MemberCardGradeService $memberCardGradeService;

    /**
     * @var callable
     */
    private $queryGradeCard;

    /**
     * @param  callable  $queryGradeCard  fn(int $companyId, array $virtualDistributorRow): ?array
     */
    public function __construct(
        DistributorRepository $distributorRepository,
        MemberCardGradeService $memberCardGradeService,
        callable $queryGradeCard
    ) {
        $this->distributorRepository = $distributorRepository;
        $this->memberCardGradeService = $memberCardGradeService;
        $this->queryGradeCard = $queryGradeCard;
    }

    /**
     * @return array{synced_count:int}
     */
    public function syncByCompanyId(int $companyId): array
    {
        $virtualDistributorRow = $this->distributorRepository->getInfo([
            'company_id' => $companyId,
            'distributor_self' => 1,
        ]);
        if (!is_array($virtualDistributorRow) || $virtualDistributorRow === []) {
            throw new \RuntimeException('Virtual distributor not found for loyalty grade sync.');
        }

        $queryData = ($this->queryGradeCard)($companyId, $virtualDistributorRow);
        if (!is_array($queryData)) {
            throw new \RuntimeException('Loyalty grade query returned empty result.');
        }
        $grades = $queryData['grades'] ?? null;
        if (!is_array($grades)) {
            throw new \RuntimeException('Loyalty grade query missing grades array.');
        }

        $mapped = [];
        $failures = [];
        foreach ($grades as $idx => $row) {
            if (!is_array($row)) {
                $failures[] = [
                    'index' => $idx,
                    'reason' => 'invalid_grade_row',
                    'message' => 'grade row is not array',
                ];
                continue;
            }
            $gradeId = (int) ($row['gradeId'] ?? 0);
            $gradeName = trim((string) ($row['name'] ?? ''));
            $gradeLevel = (int) ($row['id'] ?? 0);
            if ($gradeId <= 0 || $gradeName === '' || $gradeLevel <= 0) {
                $failures[] = [
                    'index' => $idx,
                    'reason' => 'invalid_grade_row',
                    'message' => 'gradeId/name/id invalid',
                    'gradeId' => $row['gradeId'] ?? null,
                    'name' => $row['name'] ?? null,
                    'id' => $row['id'] ?? null,
                ];
                continue;
            }
            $mapped[] = [
                'grade_id' => $gradeId,
                'grade_name' => $gradeName,
                'grade_level' => $gradeLevel,
            ];
        }
        if ($failures !== []) {
            throw new ShuyunOpenPlatformLoyaltyGradeSyncException(
                $failures,
                'Loyalty grade sync failed with row-level validation errors.'
            );
        }

        $this->memberCardGradeService->batchSave($companyId, $mapped, [
            'preserve_promotion_condition_on_update' => true,
        ]);

        return ['synced_count' => count($mapped)];
    }

    /**
     * 手动触发入口使用的结果包装：成功返回 synced_count，校验失败返回 failures 明细。
     *
     * @return array{ok:bool,synced_count?:int,error_code?:string,message?:string,failures?:array<int, array<string, mixed>>}
     */
    public function syncByCompanyIdWithReport(int $companyId): array
    {
        try {
            $result = $this->syncByCompanyId($companyId);

            return [
                'ok' => true,
                'synced_count' => (int) ($result['synced_count'] ?? 0),
                'failures' => [],
            ];
        } catch (ShuyunOpenPlatformLoyaltyGradeSyncException $e) {
            return [
                'ok' => false,
                'error_code' => 'LOYALTY_GRADE_SYNC_VALIDATION_FAILED',
                'message' => $e->getMessage(),
                'failures' => $e->getFailures(),
            ];
        }
    }
}
