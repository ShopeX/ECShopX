<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GoodsBundle\Repositories\ItemsCategoryRepository;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformCategorySyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

/** @see .tasks/plans/shuyun-open-platform-category-goods-sync.md */
class ShuyunOpenPlatformCategorySyncServiceTest extends \TestCase
{
    public function testReturnsFalseWhenConfigMissing(): void
    {
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn(null);
        $itemsRepo = $this->createMock(ItemsCategoryRepository::class);
        $itemsRepo->expects($this->never())->method('getInfo');
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');
        $svc = new ShuyunOpenPlatformCategorySyncService(
            $cfgRepo,
            $itemsRepo,
            $shop,
            $http,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $this->assertFalse($svc->syncCategory(1, 9));
    }

    public function testLevelOneReturnsTrueWithoutHttp(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleConfig();
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $itemsRepo = $this->createMock(ItemsCategoryRepository::class);
        $itemsRepo->method('getInfo')->willReturn([
            'category_id' => 1,
            'category_level' => 1,
            'category_name' => '一级',
            'parent_id' => 0,
        ]);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');
        $svc = new ShuyunOpenPlatformCategorySyncService(
            $cfgRepo,
            $itemsRepo,
            $shop,
            $http,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $this->assertTrue($svc->syncCategory(1, 1));
    }

    public function testLevelTwoPostsTwiceWithConcatNameAndOfflineAndCustomPlatformHeaders(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => '']);
        $row = $this->eligibleConfig();
        $row->setPlatCode('MYPLAT');
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $itemsRepo = $this->createMock(ItemsCategoryRepository::class);
        $itemsRepo->method('getInfo')->willReturnCallback(function (array $f) {
            if ((int) ($f['category_id'] ?? 0) === 10) {
                return ['category_name' => '一级', 'category_id' => 10];
            }

            return [
                'category_id' => 99,
                'category_level' => 2,
                'category_name' => '二级',
                'parent_id' => 10,
                'created' => '2020-01-01 00:00:00',
                'updated' => '2020-01-02 00:00:00',
            ];
        });
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformCategorySyncService(
            $cfgRepo,
            $itemsRepo,
            $shop,
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $this->assertTrue($svc->syncCategory(1, 99));
        $this->assertCount(1, $container);
        $this->assertSame('offline', $container[0]['request']->getHeaderLine('platform'));
        $this->assertSame(ShuyunOpenPlatformCategorySyncService::GATEWAY_ACTION_CATEGORY_SYNC, $container[0]['request']->getHeaderLine('Gateway-Action-Method'));
        $payloadOffline = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('一级/二级', $payloadOffline['category_name'] ?? null);
        $this->assertSame('0', $payloadOffline['parent_category_id'] ?? null);
        $this->assertSame('99', $payloadOffline['category_id'] ?? null);
    }

    public function testOnlyOfflineWhenTenantPlatEmpty(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => '']);
        $row = $this->eligibleConfig();
        $row->setPlatCode('');
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $itemsRepo = $this->createMock(ItemsCategoryRepository::class);
        $itemsRepo->method('getInfo')->willReturn([
            'category_id' => 20,
            'category_level' => 3,
            'category_name' => '三级',
            'parent_id' => 5,
            'created' => null,
            'updated' => null,
        ]);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformCategorySyncService(
            $cfgRepo,
            $itemsRepo,
            $shop,
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $this->assertTrue($svc->syncCategory(1, 20));
        $this->assertCount(1, $container);
        $this->assertSame('offline', $container[0]['request']->getHeaderLine('platform'));
        $body = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('三级', $body['category_name'] ?? null);
        $this->assertSame('5', $body['parent_category_id'] ?? null);
        $this->assertSame('20', $body['category_id'] ?? null);
    }

    public function testBusinessFailureReturnsFalse(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleConfig();
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $itemsRepo = $this->createMock(ItemsCategoryRepository::class);
        $itemsRepo->method('getInfo')->willReturn([
            'category_id' => 20,
            'category_level' => 3,
            'category_name' => '三级',
            'parent_id' => 5,
        ]);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);
        $mock = new MockHandler([
            new Response(200, [], '{"code":40001,"data":null,"msg":"err"}'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformCategorySyncService(
            $cfgRepo,
            $itemsRepo,
            $shop,
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $this->assertFalse($svc->syncCategory(1, 20));
    }

    private function eligibleConfig(): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId(1);
        $e->setAuthValue('av');
        $e->setPlatCode('pl');
        $e->setAppId('aid');
        $e->setAppSecret('sec');
        $e->setAccessToken('tok');
        $e->setIsEnabled(1);

        return $e;
    }
}
