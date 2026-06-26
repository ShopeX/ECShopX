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
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberRegisterService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

class ShuyunOpenPlatformMemberRegisterServiceTest extends \TestCase
{
    public function testRegisterSingleUsesDistributorIdAndDefaultPlatCode(): void
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
            new Response(200, [], '{"code":10000,"data":{},"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformMemberRegisterService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $ok = $svc->registerSingle(
            1,
            ['distributor_id' => 112345566, 'shop_code' => 'SHOULD_NOT_USE'],
            'member-id-1',
            '15300000001',
            'union-1',
            '张三'
        );

        $this->assertTrue($ok);
        $req = $container[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('shuyun.loyalty.member.register', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));

        $body = (string) $req->getBody();
        $this->assertStringContainsString('"id":"member-id-1"', $body);
        $this->assertStringContainsString('"platCode":"OFFLINE"', $body);
        $this->assertStringContainsString('"shopId":"112345566-off"', $body);
        $this->assertStringContainsString('"mobile":"15300000001"', $body);
        $this->assertStringContainsString('"omid":"union-1"', $body);
    }

    public function testRegisterSingleForceOfflineIgnoresDefaultPlatCode(): void
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
            new Response(200, [], '{"code":10000,"data":{},"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformMemberRegisterService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $ok = $svc->registerSingle(
            1,
            ['distributor_id' => 10],
            '99',
            '15300000002',
            null,
            null,
            true
        );

        $this->assertTrue($ok);
        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $body = (string) $req->getBody();
        $this->assertStringContainsString('"platCode":"OFFLINE"', $body);
        $this->assertStringContainsString('"shopId":"10-off"', $body);
    }

    public function testRegisterSingleThrowsWhenGatewayBusinessFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shuyun member.register failed');

        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'ABC']);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":14000,"data":{},"msg":"参数异常"}'),
        ]));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformMemberRegisterService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $svc->registerSingle(
            1,
            ['distributor_id' => 112345566],
            'member-id-1',
            '15300000001',
            'union-1'
        );
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

