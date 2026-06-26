<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use DistributionBundle\Entities\Distributor;
use DistributionBundle\Repositories\DistributorRepository;
use Doctrine\ORM\EntityManagerInterface;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Jobs\SyncShopToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncTargetPlatCodesResolver;

/** @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md A-JOB-01 */
class SyncShopToShuyunOpenPlatformJobTest extends \TestCase
{
    public function testThrowsWhenSyncReturnsFalseWhileEligible(): void
    {
        config(['doctrine.managers' => []]);

        $shop = ['distributor_id' => 5, 'company_id' => 1, 'name' => 'S'];

        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [Distributor::class, $distRepo],
        ]);

        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);

        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRow = new CompanyShuyunOpenPlatformConfig();
        $openRow->setCompanyId(1);
        $openRow->setAuthValue('a');
        $openRow->setPlatCode('P');
        $openRow->setAppId('id');
        $openRow->setAppSecret('s');
        $openRow->setAccessToken('t');
        $openRow->setIsEnabled(1);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn($openRow);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));

        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->method('syncShop')->with(1, $shop, ['OFFLINE'])->willReturn(false);
        $svc->method('isEligible')->willReturnCallback(function ($cfg) {
            return $cfg instanceof CompanyShuyunOpenPlatformConfig;
        });
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shuyun open platform shop sync failed.');

        (new SyncShopToShuyunOpenPlatformJob(1, 5))->handle();
    }

    public function testReturnsTrueWhenSyncFalseButNotEligible(): void
    {
        config(['doctrine.managers' => []]);

        $shop = ['distributor_id' => 5, 'company_id' => 1, 'name' => 'S'];

        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [Distributor::class, $distRepo],
        ]);

        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);

        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));

        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->method('syncShop')->willReturn(false);
        $svc->method('isEligible')->with(null)->willReturn(false);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        $this->assertTrue((new SyncShopToShuyunOpenPlatformJob(1, 5))->handle());
    }

    /** 闭店须同步数云（双 plat），参见 shuyun-virtual-shop-open-sync 2.2 / T1 */
    public function testClosedLifecyclePassesTwoTargetPlatCodesToService(): void
    {
        #given
        config(['doctrine.managers' => []]);
        config(['shuyun_open_platform.default_plat_code' => 'ecshop']);
        $shop = ['distributor_id' => 5, 'company_id' => 1, 'name' => 'S', 'is_valid' => 'closed'];
        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([[Distributor::class, $distRepo]]);
        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));

        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->expects($this->once())
            ->method('syncShop')
            ->with(1, $shop, ['OFFLINE'])
            ->willReturn(true);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        #when
        $ok = (new SyncShopToShuyunOpenPlatformJob(1, 5))->handle();

        #then
        $this->assertTrue($ok);
    }

    /** @see .tasks/plans/shuyun-open-platform-shop-sync-cloud-store-split.md A-SPLIT-01 */
    public function testEnabledLifecyclePassesTwoTargetPlatCodesToService(): void
    {
        #given
        config(['doctrine.managers' => []]);
        config(['shuyun_open_platform.default_plat_code' => 'ecshop']);
        $shop = ['distributor_id' => 5, 'company_id' => 1, 'name' => 'S', 'is_valid' => 'true'];
        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([[Distributor::class, $distRepo]]);
        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));
        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->expects($this->once())
            ->method('syncShop')
            ->with(1, $shop, ['OFFLINE'])
            ->willReturn(true);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        #when
        $ok = (new SyncShopToShuyunOpenPlatformJob(1, 5))->handle();

        #then
        $this->assertTrue($ok);
    }

    /** 无线上 plat 时禁用仅 OFFLINE（warning 由 Resolver / Service 记录） */
    public function testDisabledLifecyclePassesOnlyOfflinePlatCodeToService(): void
    {
        #given
        config(['doctrine.managers' => []]);
        config(['shuyun_open_platform.default_plat_code' => '']);
        $shop = ['distributor_id' => 5, 'company_id' => 1, 'name' => 'S', 'is_valid' => 'false'];
        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([[Distributor::class, $distRepo]]);
        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));
        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->expects($this->once())
            ->method('syncShop')
            ->with(1, $shop, ['OFFLINE'])
            ->willReturn(true);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        #when
        $ok = (new SyncShopToShuyunOpenPlatformJob(1, 5))->handle();

        #then
        $this->assertTrue($ok);
    }

    /** 禁用 + 默认线上 plat：Job 仅传 OFFLINE target，不调自定义平台批量注册 */
    public function testDisabledLifecyclePassesOnlyOfflineTargetWhenDefaultPlatConfigured(): void
    {
        config(['doctrine.managers' => []]);
        config(['shuyun_open_platform.default_plat_code' => 'ecshop']);
        $shop = ['distributor_id' => 5, 'company_id' => 1, 'name' => 'S', 'is_valid' => 'false'];
        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([[Distributor::class, $distRepo]]);
        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));
        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->expects($this->once())
            ->method('syncShop')
            ->with(1, $shop, ['OFFLINE'])
            ->willReturn(true);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        $this->assertTrue((new SyncShopToShuyunOpenPlatformJob(1, 5))->handle());
    }

    /** 废弃须同步数云（双 plat status=0），参见 shuyun-virtual-shop-open-sync 2.2 / T1 */
    public function testDeleteLifecyclePassesTwoTargetPlatCodesToService(): void
    {
        #given
        config(['doctrine.managers' => []]);
        config(['shuyun_open_platform.default_plat_code' => 'ecshop']);
        $shop = ['distributor_id' => 5, 'company_id' => 1, 'name' => 'S', 'is_valid' => 'delete'];
        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([[Distributor::class, $distRepo]]);
        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));
        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->expects($this->once())
            ->method('syncShop')
            ->with(1, $shop, ['OFFLINE'])
            ->willReturn(true);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        #when
        $ok = (new SyncShopToShuyunOpenPlatformJob(1, 5))->handle();

        #then
        $this->assertTrue($ok);
    }

    /** 虚拟店同样仅 OFFLINE（shuyun-offline-only） */
    public function testVirtualShopPassesOnlyOnlineTargetPlatToService(): void
    {
        config(['doctrine.managers' => []]);
        config(['shuyun_open_platform.default_plat_code' => 'ecshop']);
        $shop = [
            'distributor_id' => 5,
            'company_id' => 1,
            'name' => '虚',
            'is_valid' => 'true',
            'distributor_self' => '1',
        ];
        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([[Distributor::class, $distRepo]]);
        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));
        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->expects($this->once())
            ->method('syncShop')
            ->with(1, $shop, ['OFFLINE'])
            ->willReturn(true);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        $this->assertTrue((new SyncShopToShuyunOpenPlatformJob(1, 5))->handle());
    }

    /** 虚拟店始终 OFFLINE，仍会 syncShop */
    public function testVirtualShopWithNoCustomPlatSkipsSyncShop(): void
    {
        config(['doctrine.managers' => []]);
        config(['shuyun_open_platform.default_plat_code' => '']);
        $shop = [
            'distributor_id' => 5,
            'company_id' => 1,
            'name' => '虚',
            'is_valid' => 'true',
            'distributor_self' => '1',
        ];
        $distRepo = $this->createMock(DistributorRepository::class);
        $distRepo->method('getInfo')->willReturn($shop);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([[Distributor::class, $distRepo]]);
        $registry = new class($em) {
            public function __construct(private EntityManagerInterface $em)
            {
            }

            public function getManager(string $name): EntityManagerInterface
            {
                return $this->em;
            }
        };
        $this->app->instance('registry', $registry);
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class, new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver(
        ));
        $svc = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $svc->expects($this->once())
            ->method('syncShop')
            ->with(1, $shop, ['OFFLINE'])
            ->willReturn(true);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $svc);

        $this->assertTrue((new SyncShopToShuyunOpenPlatformJob(1, 5))->handle());
    }
}
