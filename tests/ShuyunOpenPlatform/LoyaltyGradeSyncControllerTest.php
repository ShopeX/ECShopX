<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response\Factory;
use DistributionBundle\Repositories\DistributorRepository;
use Mockery;
use OpenapiBundle\Services\Member\MemberCardGradeService;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Http\Api\V1\Action\LoyaltyGradeSyncController;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeSyncService;

class LoyaltyGradeSyncControllerTest extends \TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function bindAuthWithCompanyId(int $companyId): void
    {
        $user = Mockery::mock();
        $user->shouldReceive('get')->with('company_id')->andReturn($companyId);
        $authGuard = Mockery::mock();
        $authGuard->shouldReceive('user')->once()->andReturn($user);
        app()->instance('auth', $authGuard);
    }

    public function testPostManualSyncReturnsSuccessReportFromService(): void
    {
        $this->bindAuthWithCompanyId(1001);

        $distributorRepo = $this->createMock(DistributorRepository::class);
        $distributorRepo->method('getInfo')->willReturn([
            'distributor_id' => 112345566,
            'distributor_self' => 1,
        ]);
        $gradeService = $this->createMock(MemberCardGradeService::class);
        $gradeService->expects($this->once())->method('batchSave');
        $service = new ShuyunOpenPlatformLoyaltyGradeSyncService(
            $distributorRepo,
            $gradeService,
            static function (): array {
                return [
                    'grades' => [
                        ['gradeId' => 1, 'name' => '普通会员', 'id' => 1],
                        ['gradeId' => 2, 'name' => '高级会员', 'id' => 2],
                        ['gradeId' => 3, 'name' => 'VIP会员', 'id' => 3],
                    ],
                ];
            }
        );
        app()->instance(ShuyunOpenPlatformLoyaltyGradeSyncService::class, $service);

        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('array')->once()->with([
            'ok' => true,
            'synced_count' => 3,
        ])->andReturnUsing(static fn (array $payload): array => $payload);
        app()->instance(Factory::class, $factory);

        $controller = new LoyaltyGradeSyncController();
        $result = $controller->postManualSync();

        $this->assertSame(true, $result['ok']);
        $this->assertSame(3, $result['synced_count']);
    }

    public function testPostManualSyncReturnsFailureDetailsFromService(): void
    {
        $this->bindAuthWithCompanyId(1001);

        $distributorRepo = $this->createMock(DistributorRepository::class);
        $distributorRepo->method('getInfo')->willReturn([
            'distributor_id' => 112345566,
            'distributor_self' => 1,
        ]);
        $gradeService = $this->createMock(MemberCardGradeService::class);
        $gradeService->expects($this->never())->method('batchSave');
        $service = new ShuyunOpenPlatformLoyaltyGradeSyncService(
            $distributorRepo,
            $gradeService,
            static function (): array {
                return [
                    'grades' => [
                        ['gradeId' => 1, 'name' => '普通会员', 'id' => 1],
                        ['name' => '坏数据', 'id' => 2],
                    ],
                ];
            }
        );
        app()->instance(ShuyunOpenPlatformLoyaltyGradeSyncService::class, $service);

        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('array')->once()->andReturnUsing(static fn (array $payload): array => $payload);
        app()->instance(Factory::class, $factory);

        $controller = new LoyaltyGradeSyncController();
        $result = $controller->postManualSync();

        $this->assertSame(false, $result['ok']);
        $this->assertSame('LOYALTY_GRADE_SYNC_VALIDATION_FAILED', $result['error_code']);
        $this->assertCount(1, $result['failures']);
        $this->assertSame(1, $result['failures'][0]['index']);
    }

    public function testPostManualSyncThrowsResourceExceptionWhenGatewayBusinessFails(): void
    {
        $this->bindAuthWithCompanyId(1001);

        $distributorRepo = $this->createMock(DistributorRepository::class);
        $distributorRepo->method('getInfo')->willReturn([
            'distributor_id' => 112345566,
            'distributor_self' => 1,
        ]);
        $gradeService = $this->createMock(MemberCardGradeService::class);
        $gradeService->expects($this->never())->method('batchSave');
        $service = new ShuyunOpenPlatformLoyaltyGradeSyncService(
            $distributorRepo,
            $gradeService,
            static function (): void {
                throw new ShuyunGatewayBusinessException(11003, 'sign校验失败');
            }
        );
        app()->instance(ShuyunOpenPlatformLoyaltyGradeSyncService::class, $service);

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('sign校验失败');

        $controller = new LoyaltyGradeSyncController();
        $controller->postManualSync();
    }
}

