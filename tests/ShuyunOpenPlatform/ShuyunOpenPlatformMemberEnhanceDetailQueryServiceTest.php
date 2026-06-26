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
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberEnhanceDetailQueryService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

class ShuyunOpenPlatformMemberEnhanceDetailQueryServiceTest extends \TestCase
{
    public function testQueryDetailSendsGetWithExpectedQueryAndTenant(): void
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
            new Response(200, [], '{"code":10000,"success":true,"data":{"pointAsserts":1,"negativePoint":0}}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformMemberEnhanceDetailQueryService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $data = $svc->queryDetail(1, ['distributor_id' => 76, 'distributor_self' => 1], '1120', '100114257265');

        $this->assertSame(['pointAsserts' => 1, 'negativePoint' => 0], $data);
        $req = $container[0]['request'];
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('shuyun.loyalty.enhance.member.query.detail', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $uri = (string) $req->getUri();
        $this->assertStringContainsString('id=1120', $uri);
        $this->assertStringContainsString('platCode=OFFLINE', $uri);
        $this->assertStringContainsString('shopId=76', $uri);
        $this->assertStringContainsString('tenant=qiushi6', $uri);
        $this->assertStringContainsString('memberId=100114257265', $uri);
    }

    public function testQueryDetailOmitsMemberIdWhenNullOrBlank(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'ABC']);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":{}}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformMemberEnhanceDetailQueryService(
            $cfgRepo,
            $shopEligibility,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
        $svc->queryDetail(1, ['distributor_id' => 76, 'distributor_self' => 1], '1120', '  ');

        $uri = (string) $container[0]['request']->getUri();
        $this->assertStringNotContainsString('memberId=', $uri);
    }

    private function eligibleConfig(): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId(1);
        $e->setAuthValue('qiushi6');
        $e->setAppId('aid');
        $e->setAppSecret('sec');
        $e->setAccessToken('tok');
        $e->setIsEnabled(1);

        return $e;
    }
}
