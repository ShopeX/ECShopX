<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformScheduledTokenRefreshRunner;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenRefreshServiceInterface;

class ShuyunOpenPlatformScheduledTokenRefreshRunnerTest extends TestCase
{
    private function row(int $companyId = 1): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId($companyId);
        $e->setAuthValue('av');
        $e->setAppId('9');
        $e->setAppSecret('s');
        $e->setAccessToken('tok');

        return $e;
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-SCHED-01 */
    public function testInvokesTokenRefreshServiceForEachEligibleRow(): void
    {
        $r1 = $this->row(10);
        $r2 = $this->row(20);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findEligibleForScheduledRefresh')->willReturn([$r1, $r2]);

        $refresh = $this->createMock(ShuyunOpenPlatformTokenRefreshServiceInterface::class);
        $refresh->expects($this->exactly(2))->method('triggerRefresh')->withConsecutive([$r1, false], [$r2, false])->willReturnOnConsecutiveCalls(true, false);

        $runner = new ShuyunOpenPlatformScheduledTokenRefreshRunner($repo, $refresh);
        $stats = $runner->run(null);
        $this->assertSame(['attempted' => 2, 'ok' => 1, 'failed' => 1], $stats);
    }

    public function testRunWithCompanyIdDelegatesToFindOneByCompanyId(): void
    {
        $row = $this->row(5);
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->once())->method('findOneByCompanyId')->with(5)->willReturn($row);
        $repo->expects($this->never())->method('findEligibleForScheduledRefresh');

        $refresh = $this->createMock(ShuyunOpenPlatformTokenRefreshServiceInterface::class);
        $refresh->expects($this->once())->method('triggerRefresh')->with($row, true)->willReturn(true);

        $stats = (new ShuyunOpenPlatformScheduledTokenRefreshRunner($repo, $refresh))->run(5);
        $this->assertSame(['attempted' => 1, 'ok' => 1, 'failed' => 0], $stats);
    }

    public function testUnknownCompanyResultsInZeroAttempts(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn(null);

        $refresh = $this->createMock(ShuyunOpenPlatformTokenRefreshServiceInterface::class);
        $refresh->expects($this->never())->method('triggerRefresh');

        $stats = (new ShuyunOpenPlatformScheduledTokenRefreshRunner($repo, $refresh))->run(999);
        $this->assertSame(['attempted' => 0, 'ok' => 0, 'failed' => 0], $stats);
    }
}
