<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberInfoQueryService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberModifyService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

class ShuyunOpenPlatformMemberQueryModifyServiceTest extends \TestCase
{
    public function testQuerySinglePostsEnhanceMemberAction(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'ABC']);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"code":10000,"data":{"name":"n"},"msg":""}')]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformMemberInfoQueryService($cfgRepo, $shopEligibility, new ShuyunOpenPlatformGatewayShopIdResolver(), $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $data = $svc->querySingle(1, ['distributor_id' => 112345566], 'member-id-1');

        $this->assertSame(['name' => 'n'], $data);
        $req = $container[0]['request'];
        $this->assertSame('shuyun.loyalty.enhance.member.post', $req->getHeaderLine('Gateway-Action-Method'));
    }

    public function testModifySingleUsesPutAndAction(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'ABC']);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"code":10000,"data":{},"msg":""}')]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformMemberModifyService($cfgRepo, $shopEligibility, new ShuyunOpenPlatformGatewayShopIdResolver(), $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $ok = $svc->modifySingle(1, ['distributor_id' => 112345566], 'member-id-1', ['name' => '张三']);

        $this->assertTrue($ok);
        $req = $container[0]['request'];
        $this->assertSame('PUT', $req->getMethod());
        $this->assertSame('shuyun.loyalty.member.modify', $req->getHeaderLine('Gateway-Action-Method'));
    }

    public function testQuerySingleOfflineUsesSuffixedShopId(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"code":10000,"data":{"name":"n"},"msg":""}')]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformMemberInfoQueryService($cfgRepo, $shopEligibility, new ShuyunOpenPlatformGatewayShopIdResolver(), $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $svc->querySingle(1, ['distributor_id' => 176], 'member-id-1');

        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $this->assertStringContainsString('"shopId":"176-off"', (string) $req->getBody());
    }

    public function testQuerySingleForceOfflineUsesOfflinePlatCodeAndShopId(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'NNORMALDTCDEV2']);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"code":10000,"data":{"name":"n"},"msg":""}')]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformMemberInfoQueryService($cfgRepo, $shopEligibility, new ShuyunOpenPlatformGatewayShopIdResolver(), $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $svc->querySingle(1, ['distributor_id' => 176], '1140', true);

        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $this->assertStringContainsString('"platCode":"OFFLINE"', (string) $req->getBody());
        $this->assertStringContainsString('"shopId":"176-off"', (string) $req->getBody());
        $this->assertStringContainsString('"id":"1140"', (string) $req->getBody());
    }

    public function testModifySingleOfflineUsesSuffixedShopId(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"code":10000,"data":{},"msg":""}')]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformMemberModifyService($cfgRepo, $shopEligibility, new ShuyunOpenPlatformGatewayShopIdResolver(), $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $svc->modifySingle(1, ['distributor_id' => 176], 'member-id-1', ['name' => '张三']);

        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $this->assertStringContainsString('"shopId":"176-off"', (string) $req->getBody());
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

