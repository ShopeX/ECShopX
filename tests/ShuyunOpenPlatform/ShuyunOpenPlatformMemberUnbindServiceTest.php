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
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberUnbindService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

class ShuyunOpenPlatformMemberUnbindServiceTest extends \TestCase
{
    public function testUnbindSingleUsesDistributorIdAndAction(): void
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

        $svc = new ShuyunOpenPlatformMemberUnbindService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $this->assertTrue($svc->unbindSingle(1, ['distributor_id' => 112345566], 'union-1'));

        $req = $container[0]['request'];
        $this->assertSame('shuyun.loyalty.member.unbind', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertStringContainsString('"id":"union-1"', (string) $req->getBody());
        $this->assertStringContainsString('"shopId":"112345566-off"', (string) $req->getBody());
    }

    public function testUnbindSingleForceOfflineIgnoresDefaultPlatCode(): void
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

        $svc = new ShuyunOpenPlatformMemberUnbindService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $this->assertTrue($svc->unbindSingle(1, ['distributor_id' => 176], '1136', true));

        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $this->assertStringContainsString('"platCode":"OFFLINE"', (string) $req->getBody());
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

