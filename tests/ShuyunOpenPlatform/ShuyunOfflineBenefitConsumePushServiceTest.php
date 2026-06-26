<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use DistributionBundle\Repositories\DistributorRepository;
use OrdersBundle\Repositories\NormalOrdersRepository;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitConsumePushService;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitReportService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use TestCase;

/** @see .tasks/plans/shuyun-offline-benefit-coupon.md §7 T7 */
class ShuyunOfflineBenefitConsumePushServiceTest extends TestCase
{
    private function eligibleConfig(): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId(1);
        $e->setAuthValue('av');
        $e->setAppId('aid');
        $e->setAppSecret('sec');
        $e->setAccessToken('tok');
        $e->setIsEnabled(1);

        return $e;
    }

    private function sampleBatchAndItem(int $orderId): ShuyunOfflineBenefitSendItem
    {
        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(1);
        $batch->setRequestId('req-9');
        $batch->setBenefitId('ben-9');
        $batch->setSendKind('single');
        $batch->setStatus('done');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('cust-1');
        $item->setBenefitCode('CODE-1');
        $item->setStatus('SUCCESS');
        $item->setLocalOrderId($orderId);

        return $item;
    }

    private function makeService(
        CompanyShuyunOpenPlatformConfigRepository $cfgRepo,
        ShuyunOpenPlatformShopSyncService $shop,
        ShuyunOfflineBenefitReportService $report,
        ShuyunOfflineBenefitSendItemRepository $items,
        NormalOrdersRepository $orders,
        DistributorRepository $distributors,
        ?ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver = null,
    ): ShuyunOfflineBenefitConsumePushService {
        return new ShuyunOfflineBenefitConsumePushService(
            $cfgRepo,
            $shop,
            $report,
            $items,
            $orders,
            $distributors,
            $shopIdResolver ?? new ShuyunOpenPlatformGatewayShopIdResolver(),
        );
    }

    public function testHandlePaySuccessSkipsWhenTenantNotEligible(): void
    {
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->with(1)->willReturn(null);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->expects($this->never())->method('pushResultV2');
        $items = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $items->expects($this->never())->method('findSendItemsForOrderPayConsume');
        $orders = $this->createMock(NormalOrdersRepository::class);
        $distributors = $this->createMock(DistributorRepository::class);

        $svc = $this->makeService($cfgRepo, $shop, $report, $items, $orders, $distributors);
        $svc->handlePaySuccess(1, 100);
    }

    public function testHandlePaySuccessUsesOrderDistributorShopIdWithOffSuffix(): void
    {
        config([
            'shuyun_open_platform.offline_plat_id_suffix' => '-off',
            'shuyun_open_platform.offline_benefit_gateway_platform' => 'offline',
        ]);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->with(1)->willReturn($this->eligibleConfig());
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $orders = $this->createMock(NormalOrdersRepository::class);
        $orders->method('getInfo')->willReturnMap([
            [['company_id' => 1, 'order_id' => '55'], ['user_id' => 9, 'total_fee' => 100, 'distributor_id' => 42]],
            [['company_id' => 1, 'order_id' => '55'], ['user_id' => 9, 'total_fee' => 100, 'distributor_id' => 42]],
        ]);

        $distributors = $this->createMock(DistributorRepository::class);
        $distributors->method('getInfo')->with([
            'company_id' => 1,
            'distributor_id' => 42,
        ])->willReturn(['distributor_id' => 42, 'name' => '店42']);

        $item = $this->sampleBatchAndItem(55);

        $items = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $items->method('findSendItemsForOrderPayConsume')->with(1, 55)->willReturn([$item]);
        $items->expects($this->once())->method('save')->with($this->callback(function (ShuyunOfflineBenefitSendItem $i): bool {
            return $i->getLastConsumeStatus() === 'USED' && $i->getLastConsumePushAt() !== null;
        }));

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->expects($this->once())->method('pushResultV2')->with(
            1,
            'offline',
            $this->callback(function (array $rows): bool {
                return \count($rows) === 1
                    && ($rows[0]['status'] ?? null) === 'USED'
                    && ($rows[0]['orderId'] ?? null) === '55'
                    && ($rows[0]['benefitCode'] ?? null) === 'CODE-1'
                    && ($rows[0]['shopId'] ?? null) === '42-off';
            })
        )->willReturn(true);

        $svc = $this->makeService($cfgRepo, $shop, $report, $items, $orders, $distributors);
        $svc->handlePaySuccess(1, 55);
    }

    public function testHandlePaySuccessSkipsPushWhenShopIdCannotBeResolved(): void
    {
        config(['shuyun_open_platform.offline_benefit_gateway_platform' => 'offline']);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $orders = $this->createMock(NormalOrdersRepository::class);
        $orders->method('getInfo')->willReturnMap([
            [['company_id' => 1, 'order_id' => '55'], ['user_id' => 9, 'total_fee' => 100, 'distributor_id' => 0]],
            [['company_id' => 1, 'order_id' => '55'], ['user_id' => 9, 'total_fee' => 100, 'distributor_id' => 0]],
        ]);

        $distributors = $this->createMock(DistributorRepository::class);
        $distributors->expects($this->never())->method('getInfo');

        $items = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $items->expects($this->never())->method('findSendItemsForOrderPayConsume');

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->expects($this->never())->method('pushResultV2');

        $svc = $this->makeService($cfgRepo, $shop, $report, $items, $orders, $distributors);
        $svc->handlePaySuccess(1, 55);
    }

    public function testHandlePaySuccessSkipsWhenOrderNotFoundForShopId(): void
    {
        config(['shuyun_open_platform.offline_benefit_gateway_platform' => 'offline']);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $orders = $this->createMock(NormalOrdersRepository::class);
        $orders->method('getInfo')->willReturn([]);

        $distributors = $this->createMock(DistributorRepository::class);
        $items = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $items->expects($this->never())->method('findSendItemsForOrderPayConsume');
        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->expects($this->never())->method('pushResultV2');

        $svc = $this->makeService($cfgRepo, $shop, $report, $items, $orders, $distributors);
        $svc->handlePaySuccess(1, 55);
    }

    public function testHandlePaySuccessSkipsRowAlreadyMarkedUsed(): void
    {
        config(['shuyun_open_platform.offline_benefit_gateway_platform' => 'offline']);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $orders = $this->createMock(NormalOrdersRepository::class);
        $orders->method('getInfo')->willReturnMap([
            [['company_id' => 1, 'order_id' => '1'], ['user_id' => 9, 'total_fee' => 100, 'distributor_id' => 42]],
            [['company_id' => 1, 'order_id' => '1'], ['user_id' => 9, 'total_fee' => 100, 'distributor_id' => 42]],
        ]);
        $distributors = $this->createMock(DistributorRepository::class);
        $distributors->method('getInfo')->willReturn(['distributor_id' => 42]);

        $item = $this->sampleBatchAndItem(1);
        $item->setLastConsumeStatus('USED');
        $item->setLastConsumePushAt(1);

        $items = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $items->method('findSendItemsForOrderPayConsume')->willReturn([$item]);
        $items->expects($this->never())->method('save');

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->expects($this->never())->method('pushResultV2');

        $svc = $this->makeService($cfgRepo, $shop, $report, $items, $orders, $distributors);
        $svc->handlePaySuccess(1, 1);
    }

    public function testHandleOrderCancelPushesNotUsedWhenPreviouslyUsed(): void
    {
        config([
            'shuyun_open_platform.offline_plat_id_suffix' => '-off',
            'shuyun_open_platform.offline_benefit_gateway_platform' => 'offline',
        ]);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $orders = $this->createMock(NormalOrdersRepository::class);
        $orders->method('getInfo')->willReturnMap([
            [['company_id' => 1, 'order_id' => '77'], ['user_id' => 9, 'total_fee' => 100, 'distributor_id' => 42]],
            [['company_id' => 1, 'order_id' => '77'], ['user_id' => 9, 'total_fee' => 100, 'distributor_id' => 42]],
        ]);
        $distributors = $this->createMock(DistributorRepository::class);
        $distributors->method('getInfo')->willReturn(['distributor_id' => 42]);

        $item = $this->sampleBatchAndItem(77);
        $item->setLastConsumeStatus('USED');
        $item->setLastConsumePushAt(100);

        $items = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $items->method('findSendItemsForOrderCancelNotUsed')->with(1, 77)->willReturn([$item]);
        $items->expects($this->once())->method('save')->with($this->callback(function (ShuyunOfflineBenefitSendItem $i): bool {
            return $i->getLastConsumeStatus() === 'NOT_USED';
        }));

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->expects($this->once())->method('pushResultV2')->with(
            1,
            'offline',
            $this->callback(function (array $rows): bool {
                return ($rows[0]['status'] ?? null) === 'NOT_USED'
                    && ($rows[0]['orderId'] ?? null) === '77'
                    && ($rows[0]['shopId'] ?? null) === '42-off';
            })
        )->willReturn(true);

        $svc = $this->makeService($cfgRepo, $shop, $report, $items, $orders, $distributors);
        $svc->handleOrderCancel(1, 77);
    }
}
