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
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderGatewayActions;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTradeSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

class ShuyunOpenPlatformTradeSyncServiceTest extends \TestCase
{
    public function testSyncPostsTradeActionWithPlatformAndOrderArrayBody(): void
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

        $svc = new ShuyunOpenPlatformTradeSyncService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));

        $order = $this->minimalTradeOrder();
        $ok = $svc->syncValidatedTradeOrders(1, 'myplat', [$order]);

        $this->assertTrue($ok);
        $req = $container[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame(ShuyunOpenPlatformOrderGatewayActions::GATEWAY_ACTION_TRADE_SYNC, $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('myplat', $req->getHeaderLine('platform'));
        $body = (string) $req->getBody();
        $this->assertStringStartsWith('[', $body);
        $this->assertStringContainsString('"order_id":"9081283821"', $body);
    }

    public function testSyncChunksFiftyOneIntoTwoRequests(): void
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
            new Response(200, [], '{"code":10000,"data":{},"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformTradeSyncService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));
        $base = $this->minimalTradeOrder();
        $orders = [];
        for ($i = 0; $i < 51; ++$i) {
            $row = $base;
            $row['order_id'] = (string) (100000 + $i);
            $orders[] = $row;
        }

        $this->assertTrue($svc->syncValidatedTradeOrders(1, 'abc', $orders));
        $this->assertCount(2, $container);
    }

    public function testSyncReturnsFalseWhenConfigIneligible(): void
    {
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn(null);
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');

        $svc = new ShuyunOpenPlatformTradeSyncService($cfgRepo, $shopEligibility, $http, new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertFalse($svc->syncValidatedTradeOrders(1, 'offline', [$this->minimalTradeOrder()]));
    }

    public function testSyncReturnsTrueForEmptyOrders(): void
    {
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');

        $svc = new ShuyunOpenPlatformTradeSyncService($cfgRepo, $shopEligibility, $http, new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertTrue($svc->syncValidatedTradeOrders(1, 'offline', []));
    }

    public function testSyncMoneyFieldsKeepJsonNumberWithoutFloatTail(): void
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
        $svc = new ShuyunOpenPlatformTradeSyncService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));

        $order = $this->minimalTradeOrder();
        $order['payment'] = 333.33;
        $order['post_fee'] = 0.01;
        $order['trade_discount_fee'] = 0.03;
        $order['orders'][0]['price'] = 333.33;
        $order['orders'][0]['discount_fee'] = 0.01;
        $order['orders'][0]['adjust_fee'] = 0.0;

        $old = ini_get('serialize_precision');
        try {
            ini_set('serialize_precision', '17');
            $ok = $svc->syncValidatedTradeOrders(1, 'offline', [$order]);
        } finally {
            if ($old !== false) {
                ini_set('serialize_precision', (string) $old);
            }
        }
        $this->assertTrue($ok);

        $body = (string) $container[0]['request']->getBody();
        $this->assertStringContainsString('"payment":333.33', $body);
        $this->assertStringContainsString('"post_fee":0.01', $body);
        $this->assertStringContainsString('"trade_discount_fee":0.03', $body);
        $this->assertStringContainsString('"price":333.33', $body);
        $this->assertStringContainsString('"discount_fee":0.01', $body);
        $this->assertStringNotContainsString('333.32999999999998', $body);
        $this->assertStringNotContainsString('"payment":"333.33"', $body);
        $this->assertStringNotContainsString('"post_fee":"0.01"', $body);
        $this->assertStringNotContainsString('"discount_fee":"0.01"', $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalTradeOrder(): array
    {
        return [
            'shop_id' => '2202520',
            'plat_account' => '10001',
            'order_id' => '9081283821',
            'order_status' => 'WAIT_BUYER_PAY',
            'trade_type' => 'FIXED',
            'is_presale' => '0',
            'trade_source' => '11',
            'payment' => 121,
            'post_fee' => 10,
            'adjust_fee' => 0.7,
            'product_num' => 1,
            'created' => '2017-12-08 21:33:03',
            'modified' => '2017-12-08 21:33:03',
            'delivery_type' => 'SY_EXPRESS',
            'orders' => [
                [
                    'order_item_id' => '1223',
                    'product_id' => '908872367645891',
                    'sku_id' => '231562388901',
                    'product_name' => '新款T恤',
                    'price' => 12.22,
                    'product_num' => 1,
                    'discount_fee' => 0,
                    'adjust_fee' => 0.0,
                    'pay_time' => '2018-10-08 09:00:00',
                ],
            ],
        ];
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
