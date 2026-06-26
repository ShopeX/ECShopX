<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use DistributionBundle\Repositories\DistributorRepository;
use MembersBundle\Services\MemberService;
use ReflectionMethod;

class MemberServiceShuyunOpenDeleteUnbindResolutionTest extends \TestCase
{
    public function testResolveOpenUnbindPrefersOfflineSnapshot(): void
    {
        $svc = new MemberService();
        $rm = new ReflectionMethod(MemberService::class, 'resolveDistributorRowForOpenUnbindOnDelete');
        $rm->setAccessible(true);

        $repo = $this->createMock(DistributorRepository::class);
        $repo->expects($this->once())->method('getInfo')->with([
            'company_id' => 7,
            'distributor_id' => 200,
        ])->willReturn(['distributor_id' => 200, 'distributor_self' => 0]);

        $memberRow = ['offline_reg_distributor' => 200, 'reg_distributor' => 99, 'shuyun_open_online_wxapp_sync_at' => 1];
        [$row, $forceOffline] = $rm->invoke($svc, $repo, 7, $memberRow);
        $this->assertSame(200, (int) ($row['distributor_id'] ?? 0));
        $this->assertTrue($forceOffline);
    }

    public function testResolveOpenUnbindUsesRegWhenWxappSyncedAndNoOfflineSnapshot(): void
    {
        $svc = new MemberService();
        $rm = new ReflectionMethod(MemberService::class, 'resolveDistributorRowForOpenUnbindOnDelete');
        $rm->setAccessible(true);

        $repo = $this->createMock(DistributorRepository::class);
        $repo->expects($this->once())->method('getInfo')->with([
            'company_id' => 7,
            'distributor_id' => 55,
        ])->willReturn(['distributor_id' => 55, 'distributor_self' => 0]);

        $memberRow = ['offline_reg_distributor' => null, 'reg_distributor' => 55, 'shuyun_open_online_wxapp_sync_at' => 10];
        [$row, $forceOffline] = $rm->invoke($svc, $repo, 7, $memberRow);
        $this->assertSame(55, (int) ($row['distributor_id'] ?? 0));
        $this->assertTrue($forceOffline);
    }

    public function testResolveOpenUnbindReturnsNullWhenNoEligibleShop(): void
    {
        $svc = new MemberService();
        $rm = new ReflectionMethod(MemberService::class, 'resolveDistributorRowForOpenUnbindOnDelete');
        $rm->setAccessible(true);

        $repo = $this->createMock(DistributorRepository::class);
        $repo->expects($this->once())->method('getInfo')->willReturn(['distributor_id' => 1, 'distributor_self' => 1]);

        $memberRow = ['offline_reg_distributor' => null, 'reg_distributor' => 1, 'shuyun_open_online_wxapp_sync_at' => null];
        $this->assertNull($rm->invoke($svc, $repo, 7, $memberRow));
    }

    public function testResolveOfflinePrefersSnapshotWhenValid(): void
    {
        $svc = new MemberService();
        $rm = new ReflectionMethod(MemberService::class, 'resolveOfflineDistributorRowForShuyunOpenDeleteMembers');
        $rm->setAccessible(true);

        $repo = $this->createMock(DistributorRepository::class);
        $repo->expects($this->once())->method('getInfo')->with([
            'company_id' => 7,
            'distributor_id' => 200,
        ])->willReturn(['distributor_id' => 200, 'distributor_self' => 0]);

        $memberRow = ['user_id' => 1, 'offline_reg_distributor' => 200, 'reg_distributor' => 99];
        $row = $rm->invoke($svc, $repo, 7, $memberRow, true);
        $this->assertSame(200, (int) ($row['distributor_id'] ?? 0));
    }

    public function testResolveOfflineFallsBackToRegWhenSnapshotMissing(): void
    {
        $svc = new MemberService();
        $rm = new ReflectionMethod(MemberService::class, 'resolveOfflineDistributorRowForShuyunOpenDeleteMembers');
        $rm->setAccessible(true);

        $repo = $this->createMock(DistributorRepository::class);
        $repo->expects($this->once())->method('getInfo')->with([
            'company_id' => 7,
            'distributor_id' => 55,
        ])->willReturn(['distributor_id' => 55, 'distributor_self' => 0]);

        $memberRow = ['user_id' => 1, 'offline_reg_distributor' => null, 'reg_distributor' => 55];
        $row = $rm->invoke($svc, $repo, 7, $memberRow, true);
        $this->assertSame(55, (int) ($row['distributor_id'] ?? 0));
    }

    public function testResolveOfflineReturnsNullWhenRegIsVirtual(): void
    {
        $svc = new MemberService();
        $rm = new ReflectionMethod(MemberService::class, 'resolveOfflineDistributorRowForShuyunOpenDeleteMembers');
        $rm->setAccessible(true);

        $repo = $this->createMock(DistributorRepository::class);
        $repo->expects($this->once())->method('getInfo')->with([
            'company_id' => 7,
            'distributor_id' => 1,
        ])->willReturn(['distributor_id' => 1, 'distributor_self' => 1]);

        $memberRow = ['user_id' => 1, 'offline_reg_distributor' => null, 'reg_distributor' => 1];
        $this->assertNull($rm->invoke($svc, $repo, 7, $memberRow, true));
    }
}
