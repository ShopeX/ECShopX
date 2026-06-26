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
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTradeSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

/**
 * 计划 **Acceptance & Test Cases** 中与网关 HTTP 相关的条目 — Guzzle MockHandler 录制请求，不连外网。
 *
 * @see `.tasks/plans/shuyun-open-platform-order.md` A-ORD-FWD-02Z、FWD-03、REV-04、REV-05、GATE-02、PLAT（body 无 platform）
 */
class ShuyunOpenPlatformOrderAcceptanceMockTest extends \TestCase
{
    /** A-ORD-FWD-02Z：零元单仍 POST trade.sync，金额字段允许为 0（D-ZERO-01） */
    public function testAcceptanceFwd02ZeroYuanTradeSyncPayload(): void
    {
        $container = [];
        $svc = $this->tradeServiceWithHistory($container, [
            new Response(200, [], '{"code":10000,"data":{},"msg":""}'),
        ]);

        $order = $this->minimalTradeOrder();
        $order['payment'] = 0.0;
        $order['post_fee'] = 0.0;
        $order['trade_discount_fee'] = 0.0;

        $this->assertTrue($svc->syncValidatedTradeOrders(1, 'myplat', [$order]));

        $body = (string) $container[0]['request']->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey(0, $decoded);
        $this->assertSame(0.0, (float) ($decoded[0]['payment'] ?? -1));
    }

    /** A-ORD-FWD-03：51 条 → 2 次 POST（50+1） */
    public function testAcceptanceFwd03FiftyOneTradeOrdersTwoPosts(): void
    {
        $container = [];
        $svc = $this->tradeServiceWithHistory($container, [
            new Response(200, [], '{"code":10000,"data":{},"msg":""}'),
            new Response(200, [], '{"code":10000,"data":{},"msg":""}'),
        ]);

        $base = $this->minimalTradeOrder();
        $orders = [];
        for ($i = 0; $i < 51; ++$i) {
            $row = $base;
            $row['order_id'] = (string) (900000 + $i);
            $orders[] = $row;
        }

        $this->assertTrue($svc->syncValidatedTradeOrders(1, 'wxplat', $orders));
        $this->assertCount(2, $container);
        $this->assertSame(ShuyunOpenPlatformOrderGatewayActions::GATEWAY_ACTION_TRADE_SYNC, $container[0]['request']->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame(ShuyunOpenPlatformOrderGatewayActions::GATEWAY_ACTION_TRADE_SYNC, $container[1]['request']->getHeaderLine('Gateway-Action-Method'));
    }

    /** 正向 body 根为订单数组，元素内不传 `platform`（仅 Header） */
    public function testAcceptanceTradeBodyJsonRootIsArrayWithoutPlatformKey(): void
    {
        $container = [];
        $svc = $this->tradeServiceWithHistory($container, [
            new Response(200, [], '{"code":10000,"data":{},"msg":""}'),
        ]);

        $this->assertTrue($svc->syncValidatedTradeOrders(1, 'offline', [$this->minimalTradeOrder()]));

        $decoded = json_decode((string) $container[0]['request']->getBody(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey(0, $decoded);
        $this->assertArrayNotHasKey('platform', $decoded[0]);
    }

    /** A-ORD-GATE-02：网关业务码非 10000 → sync 返回 false（ERROR 由 Service 内记录） */
    public function testAcceptanceGate02BusinessNonSuccessReturnsFalse(): void
    {
        $container = [];
        $svc = $this->tradeServiceWithHistory($container, [
            new Response(200, [], '{"code":11008,"msg":"access invalid","data":null}'),
        ]);

        $this->assertFalse($svc->syncValidatedTradeOrders(1, 'offline', [$this->minimalTradeOrder()]));
        $this->assertCount(1, $container);
    }

    /** A-ORD-REV-04：同一 refund_id 先审核中再成功，两次 sync，末次 body 含 SY_REFUND_SUCC */
    public function testAcceptanceRev04RefundResyncSecondPayloadSuccess(): void
    {
        $container = [];
        $svc = $this->refundServiceWithHistory($container, [
            new Response(200, [], '{"code":10000,"data":"success","msg":""}'),
            new Response(200, [], '{"code":10000,"data":"success","msg":""}'),
        ]);

        $base = $this->minimalRefundPayload();
        $checking = $base;
        $checking['refund_status'] = 'SY_CHECKING';
        $success = $base;
        $success['refund_status'] = 'SY_REFUND_SUCC';

        $this->assertTrue($svc->syncValidatedRefunds(1, 'offline', [$checking]));
        $this->assertTrue($svc->syncValidatedRefunds(1, 'offline', [$success]));

        $this->assertCount(2, $container);
        $body1 = (string) $container[0]['request']->getBody();
        $body2 = (string) $container[1]['request']->getBody();
        $this->assertStringContainsString('SY_CHECKING', $body1);
        $this->assertStringContainsString('SY_REFUND_SUCC', $body2);
        $this->assertStringContainsString('"refund_id":"acc-mock-1"', $body2);
    }

    /** A-ORD-REV-05：51 条退款 → 2 次 POST */
    public function testAcceptanceRev05FiftyOneRefundsTwoPosts(): void
    {
        $container = [];
        $svc = $this->refundServiceWithHistory($container, [
            new Response(200, [], '{"code":10000,"data":"success","msg":""}'),
            new Response(200, [], '{"code":10000,"data":"success","msg":""}'),
        ]);

        $base = $this->minimalRefundPayload();
        $refunds = [];
        for ($i = 0; $i < 51; ++$i) {
            $r = $base;
            $r['refund_id'] = 'r'.(700000 + $i);
            $refunds[] = $r;
        }

        $this->assertTrue($svc->syncValidatedRefunds(1, 'offline', $refunds));
        $this->assertCount(2, $container);
        $this->assertSame(ShuyunOpenPlatformOrderGatewayActions::GATEWAY_ACTION_REFUND_SYNC, $container[0]['request']->getHeaderLine('Gateway-Action-Method'));
    }

    /** 逆向 refund body 数组元素内不传 `platform` */
    public function testAcceptanceRefundBodyElementWithoutPlatformKey(): void
    {
        $container = [];
        $svc = $this->refundServiceWithHistory($container, [
            new Response(200, [], '{"code":10000,"data":"success","msg":""}'),
        ]);

        $this->assertTrue($svc->syncValidatedRefunds(1, 'offline', [$this->minimalRefundPayload()]));

        $decoded = json_decode((string) $container[0]['request']->getBody(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayNotHasKey('platform', $decoded[0]);
    }

    /**
     * @param  list<Response>  $responses
     */
    private function tradeServiceWithHistory(array &$container, array $responses): ShuyunOpenPlatformTradeSyncService
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        return new ShuyunOpenPlatformTradeSyncService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));
    }

    /**
     * @param  list<Response>  $responses
     */
    private function refundServiceWithHistory(array &$container, array $responses): ShuyunOpenPlatformRefundSyncService
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);

        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn(true);

        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        return new ShuyunOpenPlatformRefundSyncService($cfgRepo, $shopEligibility, $client, new ShuyunOpenPlatformGatewayClientFactory(null));
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
            'order_status' => 'WAIT_SELLER_SEND_GOODS',
            'trade_type' => 'FIXED',
            'is_presale' => '0',
            'trade_source' => '11',
            'payment' => 121.0,
            'post_fee' => 10.0,
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
                    'discount_fee' => 0.0,
                    'adjust_fee' => 0.0,
                    'pay_time' => '2018-10-08 09:00:00',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalRefundPayload(): array
    {
        return [
            'refund_id' => 'acc-mock-1',
            'order_id' => '22112333',
            'order_item_id' => '231433221223',
            'shop_id' => '2202520',
            'product_id' => '234221111',
            'sku_id' => '324443333',
            'refund_fee' => 0.9,
            'refund_status' => 'SY_CHECKING',
            'good_return' => 'SY_ONLY_FEE',
            'refund_reason' => 'acceptance-mock',
            'created' => '2018-06-08 12:00:34',
            'modified' => '2018-06-09 12:00:34',
            'refund_phase' => 2,
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
