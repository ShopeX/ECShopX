<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use DistributionBundle\Repositories\DistributorRepository;
use OpenapiBundle\Services\Member\MemberCardGradeService;
use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Exception\ShuyunOpenPlatformLoyaltyGradeSyncException;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeSyncService;

class ShuyunOpenPlatformLoyaltyGradeSyncServiceTest extends TestCase
{
    public function testSyncMapsGradesAndCallsBatchSave(): void
    {
        $distributorRepo = $this->createMock(DistributorRepository::class);
        $distributorRepo->method('getInfo')->with([
            'company_id' => 1,
            'distributor_self' => 1,
        ])->willReturn([
            'distributor_id' => 112345566,
            'distributor_self' => 1,
        ]);

        $queryFn = static function (int $companyId, array $virtualDistributorRow): array {
            TestCase::assertSame(1, $companyId);
            TestCase::assertArrayHasKey('distributor_id', $virtualDistributorRow);

            return [
            'grades' => [
                ['gradeId' => 12445, 'name' => '普通会员', 'id' => 1],
                ['gradeId' => 12446, 'name' => '高级会员', 'id' => 2],
            ],
            ];
        };

        $gradeService = $this->createMock(MemberCardGradeService::class);
        $gradeService->expects($this->once())->method('batchSave')->with(
            1,
            [
                ['grade_id' => 12445, 'grade_name' => '普通会员', 'grade_level' => 1],
                ['grade_id' => 12446, 'grade_name' => '高级会员', 'grade_level' => 2],
            ],
            ['preserve_promotion_condition_on_update' => true],
        );

        $sut = new ShuyunOpenPlatformLoyaltyGradeSyncService($distributorRepo, $gradeService, $queryFn);
        $result = $sut->syncByCompanyId(1);

        $this->assertSame(2, $result['synced_count']);
    }

    public function testSyncFailsAsWholeWhenAnyGradeInvalidAndDoesNotPersist(): void
    {
        $distributorRepo = $this->createMock(DistributorRepository::class);
        $distributorRepo->method('getInfo')->willReturn([
            'distributor_id' => 112345566,
            'distributor_self' => 1,
        ]);

        $queryFn = static function (): array {
            return [
            'grades' => [
                ['gradeId' => 12445, 'name' => '普通会员', 'id' => 1],
                ['name' => '坏数据', 'id' => 2],
            ],
            ];
        };

        $gradeService = $this->createMock(MemberCardGradeService::class);
        $gradeService->expects($this->never())->method('batchSave');

        $sut = new ShuyunOpenPlatformLoyaltyGradeSyncService($distributorRepo, $gradeService, $queryFn);
        try {
            $sut->syncByCompanyId(1);
            $this->fail('Expected sync exception not thrown.');
        } catch (ShuyunOpenPlatformLoyaltyGradeSyncException $e) {
            $this->assertCount(1, $e->getFailures());
            $this->assertSame(1, $e->getFailures()[0]['index'] ?? null);
            $this->assertSame('invalid_grade_row', $e->getFailures()[0]['reason'] ?? null);
        }
    }

    public function testSyncByCompanyIdWithReportReturnsSuccessPayload(): void
    {
        $distributorRepo = $this->createMock(DistributorRepository::class);
        $distributorRepo->method('getInfo')->willReturn([
            'distributor_id' => 112345566,
            'distributor_self' => 1,
        ]);

        $queryFn = static function (): array {
            return [
                'grades' => [
                    ['gradeId' => 12445, 'name' => '普通会员', 'id' => 1],
                ],
            ];
        };

        $gradeService = $this->createMock(MemberCardGradeService::class);
        $gradeService->expects($this->once())->method('batchSave');

        $sut = new ShuyunOpenPlatformLoyaltyGradeSyncService($distributorRepo, $gradeService, $queryFn);
        $result = $sut->syncByCompanyIdWithReport(1);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['synced_count']);
        $this->assertSame([], $result['failures']);
    }

    public function testSyncByCompanyIdWithReportReturnsFailuresPayloadWhenValidationFails(): void
    {
        $distributorRepo = $this->createMock(DistributorRepository::class);
        $distributorRepo->method('getInfo')->willReturn([
            'distributor_id' => 112345566,
            'distributor_self' => 1,
        ]);

        $queryFn = static function (): array {
            return [
                'grades' => [
                    ['gradeId' => 12445, 'name' => '普通会员', 'id' => 1],
                    ['name' => '坏数据', 'id' => 2],
                ],
            ];
        };

        $gradeService = $this->createMock(MemberCardGradeService::class);
        $gradeService->expects($this->never())->method('batchSave');

        $sut = new ShuyunOpenPlatformLoyaltyGradeSyncService($distributorRepo, $gradeService, $queryFn);
        $result = $sut->syncByCompanyIdWithReport(1);

        $this->assertFalse($result['ok']);
        $this->assertSame('LOYALTY_GRADE_SYNC_VALIDATION_FAILED', $result['error_code']);
        $this->assertCount(1, $result['failures']);
        $this->assertSame(1, $result['failures'][0]['index'] ?? null);
        $this->assertSame('invalid_grade_row', $result['failures'][0]['reason'] ?? null);
    }
}

