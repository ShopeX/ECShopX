<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberBindPushService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

class ShuyunOpenPlatformMemberBindPushServiceTest extends \TestCase
{
    public function testPushSingleUsesDistributorIdAndDefaultPlatCode(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'ABC']);
        config(['shuyun_open_platform.gateway_partner' => 'nnormal']);

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

        $svc = new ShuyunOpenPlatformMemberBindPushService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $ok = $svc->pushSingle(
            1,
            ['distributor_id' => 112345566, 'shop_code' => 'SHOULD_NOT_USE'],
            'plat-account-1',
            'union-1',
            'openid-1'
        );

        $this->assertTrue($ok);
        $req = $container[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('shuyun.private.bind.push', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));

        $body = (string) $req->getBody();
        $this->assertStringContainsString('"platCode":"OFFLINE"', $body);
        $this->assertStringContainsString('"shopId":"112345566-off"', $body);
        $this->assertStringContainsString('"platAccount":"plat-account-1"', $body);
        $this->assertStringContainsString('"unionId":"union-1"', $body);
        $this->assertStringContainsString('"weixinOpenId":"openid-1"', $body);
        $this->assertStringContainsString('"partner":"nnormal"', $body);
    }

    public function testPushSingleFallsBackToOfflineWhenDefaultPlatCodeEmpty(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => '   ']);
        config(['shuyun_open_platform.gateway_partner' => 'nnormal']);

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

        $svc = new ShuyunOpenPlatformMemberBindPushService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $svc->pushSingle(1, ['distributor_id' => 42], 'plat-account-1', 'union-1', 'openid-1');

        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $this->assertStringContainsString('"platCode":"OFFLINE"', (string) $req->getBody());
    }

    public function testPushSingleThrowsWhenRequiredFieldEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('platAccount is required for bind.push.');

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');
        $svc = new ShuyunOpenPlatformMemberBindPushService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $http,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        config(['shuyun_open_platform.gateway_partner' => 'nnormal']);
        $svc->pushSingle(1, ['distributor_id' => 42], '  ', 'union-1', 'openid-1');
    }

    public function testPushSingleThrowsWhenGatewayPartnerConfigEmpty(): void
    {
        config(['shuyun_open_platform.gateway_partner' => '   ']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('partner is required for bind.push (configure shuyun_open_platform.gateway_partner).');

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');
        $svc = new ShuyunOpenPlatformMemberBindPushService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $http,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $svc->pushSingle(1, ['distributor_id' => 42], 'a', 'union-1', 'openid-1');
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
