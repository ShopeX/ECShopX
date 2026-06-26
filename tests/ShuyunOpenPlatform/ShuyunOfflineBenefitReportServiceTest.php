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
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitGatewayActions;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitReportService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/** @see .tasks/plans/shuyun-offline-benefit-coupon.md §7 T2 */
class ShuyunOfflineBenefitReportServiceTest extends \TestCase
{
    public function testPushSendReportV2PostsNumericCountsAndCorrectAction(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":{},"message":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOfflineBenefitReportService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $ok = $svc->pushSendReportV2(1, 'offline', 'bid-1', 'req-1', 10000, 9000, 1000);

        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $req = $container[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame(ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_SEND_REPORT_PUSH_V2, $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $decoded = json_decode((string) $req->getBody(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('bid-1', $decoded['benefitId']);
        $this->assertSame('req-1', $decoded['requestId']);
        $this->assertSame(10000, $decoded['total']);
        $this->assertSame(9000, $decoded['success']);
        $this->assertSame(1000, $decoded['failure']);
        $this->assertIsInt($decoded['total']);
        $this->assertIsInt($decoded['success']);
        $this->assertIsInt($decoded['failure']);
    }

    public function testPushSendResultDetailPushV2PostsArrayBodyAndAction(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":{},"message":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $rows = [[
            'requestId' => 'r1',
            'benefitId' => 'b1',
            'customerId' => 'c1',
            'benefitCode' => '',
            'sendTime' => '2019-11-11 12:00:00',
            'sendReason' => '积分抽奖',
            'status' => 'FAILURE',
            'failReason' => '不是会员',
        ]];

        $svc = new ShuyunOfflineBenefitReportService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertTrue($svc->pushSendResultDetailV2(1, 'offline', $rows));

        $req = $container[0]['request'];
        $this->assertSame(ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_SEND_RESULT_DETAIL_PUSH_V2, $req->getHeaderLine('Gateway-Action-Method'));
        $decoded = json_decode((string) $req->getBody(), true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('FAILURE', $decoded[0]['status']);
    }

    public function testPushResultV2PostsArrayBodyAndAction(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":{},"message":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $rows = [[
            'benefitId' => '4353565563',
            'requestId' => '9900434',
            'benefitCode' => '53535356',
            'platCode' => 'OFFLINE',
            'shopId' => '97676578',
            'customerId' => 'io8087343',
            'status' => 'USED',
            'orderId' => '34343534242',
            'useTime' => '2018-10-01 00:00:00',
            'remark' => '核销备注',
        ]];

        $svc = new ShuyunOfflineBenefitReportService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertTrue($svc->pushResultV2(1, 'offline', $rows));

        $req = $container[0]['request'];
        $this->assertSame(ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_RESULT_PUSH_V2, $req->getHeaderLine('Gateway-Action-Method'));
        $decoded = json_decode((string) $req->getBody(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('USED', $decoded[0]['status']);
    }

    public function testPushSendReportV2SkipsHttpWhenNotEligible(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(false);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(500, [], 'should not be called'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOfflineBenefitReportService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $ok = $svc->pushSendReportV2(1, 'offline', 'bid-1', 'req-1', 1, 1, 0);

        $this->assertFalse($ok);
        $this->assertCount(0, $container);
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
