<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyCardGradeQueryService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

class ShuyunOpenPlatformLoyaltyCardGradeQueryServiceTest extends \TestCase
{
    public function testQueryUsesDistributorIdAsShopIdAndDefaultPlatCode(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'ABC']);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":{"ok":1},"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyCardGradeQueryService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver(new ShuyunOpenPlatformGatewayShopIdResolver()),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $data = $svc->queryGradeCard(1, ['distributor_id' => 112345566, 'shop_code' => 'SHOULD_NOT_USE']);

        $this->assertSame(['ok' => 1], $data);
        $req = $container[0]['request'];
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('shuyun.loyalty.card.grade.query', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $uri = (string) $req->getUri();
        $this->assertStringContainsString('shopId=112345566', $uri);
        $this->assertStringContainsString('platCode=OFFLINE', $uri);
    }

    public function testQueryFallsBackToOfflineWhenDefaultPlatCodeEmpty(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => '   ']);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":{"ok":1},"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyCardGradeQueryService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver(new ShuyunOpenPlatformGatewayShopIdResolver()),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $svc->queryGradeCard(1, ['distributor_id' => 42]);

        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $this->assertStringContainsString('platCode=OFFLINE', (string) $req->getUri());
    }

    public function testQueryPropagatesShuyunGatewayBusinessException(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'ABC']);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":11003,"gatewaySuccess":false,"message":"sign校验失败"}'),
        ]));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyCardGradeQueryService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver(new ShuyunOpenPlatformGatewayShopIdResolver()),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        $this->expectException(ShuyunGatewayBusinessException::class);
        $this->expectExceptionMessage('sign校验失败');

        $svc->queryGradeCard(1, ['distributor_id' => 112345566]);
    }

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
}
