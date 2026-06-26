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
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderGatewayActions;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformRefundSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

class ShuyunOpenPlatformRefundSyncServiceTest extends \TestCase
{
    public function testSyncPostsRefundActionWithPlatformAndArrayBody(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);

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

        $svc = new ShuyunOpenPlatformRefundSyncService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));

        $refund = [
            'refund_id' => '10694466778888',
            'order_id' => '22112333',
            'order_item_id' => '231433221223',
            'shop_id' => '2202520',
            'product_id' => '234221111',
            'sku_id' => '324443333',
            'refund_fee' => 0.9,
            'refund_status' => 'SY_CHECKING',
            'good_return' => 'SY_RETURN_FEE_GOOD',
            'refund_reason' => '测试',
            'created' => '2018-06-08 12:00:34',
            'modified' => '2018-06-09 12:00:34',
            'refund_phase' => 2,
        ];

        $this->assertTrue($svc->syncValidatedRefunds(1, 'offline', [$refund]));

        $req = $container[0]['request'];
        $this->assertSame(ShuyunOpenPlatformOrderGatewayActions::GATEWAY_ACTION_REFUND_SYNC, $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $this->assertStringContainsString('"refund_id":"10694466778888"', (string) $req->getBody());
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
