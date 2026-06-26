<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Gateway\ShuyunGatewayClient;
use ShuyunOpenPlatformBundle\Gateway\ShuyunSigner;

class ShuyunGatewayClientTest extends TestCase
{
    /** @see .tasks/plans/shuyun-open-platform-core.md TC-CLI-01 */
    public function testMockSuccessHttp200AndBusinessSuccess(): void
    {
        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":{"k":"v"},"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);

        $sut = new ShuyunGatewayClient('aid', 'secret', 'http://open-api.test/', $client);
        $result = $sut->postJson('shuyun.test.method', ['a' => 1]);
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['k' => 'v'], $result->getData());
        $req = $container[0]['request'];
        $t = $req->getHeaderLine('Gateway-Request-Time');
        $expectedSign = (new ShuyunSigner())->sign('secret', ['Gateway-Request-Time' => $t]);
        $this->assertSame($expectedSign, $req->getHeaderLine('Gateway-Sign'), 'POST JSON body 不参与签名');
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-CLI-02 */
    public function testMockHttp200BusinessFailureThrows(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"code":50001,"data":null,"msg":"fail"}'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'http://open-api.test']);

        $sut = new ShuyunGatewayClient('aid', 'secret', 'http://open-api.test/', $client);
        $this->expectException(ShuyunGatewayBusinessException::class);
        $sut->postJson('shuyun.test.method', []);
    }

    public function testBusinessFailureUsesMessageFieldWhenMsgEmpty(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"code":10999,"data":null,"msg":"","message":"header 缺参"}'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'http://open-api.test']);
        $sut = new ShuyunGatewayClient('aid', 'secret', 'http://open-api.test/', $client);
        try {
            $sut->postJson('shuyun.test.method', []);
            $this->fail('expected ShuyunGatewayBusinessException');
        } catch (ShuyunGatewayBusinessException $e) {
            $this->assertSame('header 缺参', $e->getMessage());
            $this->assertSame(10999, $e->getBusinessCode());
        }
    }

    public function testBusinessFailureFallbackIncludesCodeWhenNoMsgOrMessage(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"code":888,"data":"detail text","msg":""}'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'http://open-api.test']);
        $sut = new ShuyunGatewayClient('aid', 'secret', 'http://open-api.test/', $client);
        try {
            $sut->postJson('shuyun.test.method', []);
            $this->fail('expected ShuyunGatewayBusinessException');
        } catch (ShuyunGatewayBusinessException $e) {
            $this->assertStringContainsString('888', $e->getMessage());
            $this->assertStringContainsString('detail text', $e->getMessage());
        }
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-HDR-01 */
    public function testFirstClassInterfaceNoAccessTokenHeader(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);

        $sut = new ShuyunGatewayClient('myAppId', 'sec', 'http://open-api.test/', $client);
        $sut->postJson('m', []);

        $this->assertNotEmpty($container);
        /** @var \GuzzleHttp\Psr7\Request $req */
        $req = $container[0]['request'];
        $this->assertFalse($req->hasHeader('Gateway-Access-Token'));
        $this->assertSame('myAppId', $req->getHeaderLine('Gateway-Authid'));
        $this->assertSame('m', $req->getHeaderLine('Gateway-Action-Method'));
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-HDR-02 */
    public function testSecondClassInterfaceIncludesAccessToken(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);

        $sut = new ShuyunGatewayClient('aid', 'sec', 'http://open-api.test/', $client);
        $sut->postJson('m', [], 'tok123');

        $req = $container[0]['request'];
        $this->assertSame('tok123', $req->getHeaderLine('Gateway-Access-Token'));
    }

    /** @see .tasks/plans/shuyun-open-platform-category-goods-sync.md A-PLAT-02 */
    public function testOptionalPlatformHeaderIsLowercased(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);

        $sut = new ShuyunGatewayClient('aid', 'sec', 'http://open-api.test/', $client);
        $sut->postJson('m', [], 'tok', '  EcShOp  ');

        $req = $container[0]['request'];
        $this->assertSame('ecshop', $req->getHeaderLine('platform'));
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-RSP-04 */
    public function testHttp500EmptyBodyThrowsHttpException(): void
    {
        $mock = new MockHandler([
            new Response(500, [], ''),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'http://open-api.test']);
        $sut = new ShuyunGatewayClient('a', 's', 'http://open-api.test/', $client);
        $this->expectException(ShuyunGatewayHttpException::class);
        $sut->postJson('m', []);
    }

    public function testGetQuerySendsMethodAndQueryAndPlatformHeader(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":{"ok":1},"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);

        $sut = new ShuyunGatewayClient('aid', 'sec', 'http://open-api.test/', $client);
        $result = $sut->getQuery('shuyun.test.get', ['shopId' => '123'], 'tok', 'OFFLINE');

        $this->assertTrue($result->isSuccess());
        $req = $container[0]['request'];
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('shuyun.test.get', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('tok', $req->getHeaderLine('Gateway-Access-Token'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $this->assertStringContainsString('shopId=123', (string) $req->getUri());
        $t = $req->getHeaderLine('Gateway-Request-Time');
        $expectedSign = (new ShuyunSigner())->sign('sec', [
            'Gateway-Request-Time' => $t,
            'shopId' => '123',
        ]);
        $this->assertSame($expectedSign, $req->getHeaderLine('Gateway-Sign'), 'GET 签名须包含 query 与 Gateway-Request-Time（数云文档 3.2）');
    }

    public function testGetQuerySignIncludesMultipleQueryParamsSortedWithTime(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":{"ok":1},"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);

        $sut = new ShuyunGatewayClient('aid', 'sec', 'http://open-api.test/', $client);
        $sut->getQuery('shuyun.loyalty.card.grade.query', [
            'shopId' => '76',
            'platCode' => 'NNORMALDTCDEV2',
        ], 'tok', 'nnormaldtcdev2');

        $req = $container[0]['request'];
        $t = $req->getHeaderLine('Gateway-Request-Time');
        $expectedSign = (new ShuyunSigner())->sign('sec', [
            'Gateway-Request-Time' => $t,
            'platCode' => 'NNORMALDTCDEV2',
            'shopId' => '76',
        ]);
        $this->assertSame($expectedSign, $req->getHeaderLine('Gateway-Sign'));
    }

    public function testPutJsonSendsMethodAndJsonBody(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":{"ok":1},"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);

        $sut = new ShuyunGatewayClient('aid', 'sec', 'http://open-api.test/', $client);
        $result = $sut->putJson('shuyun.test.put', ['name' => 'n'], 'tok');

        $this->assertTrue($result->isSuccess());
        $req = $container[0]['request'];
        $this->assertSame('PUT', $req->getMethod());
        $this->assertSame('shuyun.test.put', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('tok', $req->getHeaderLine('Gateway-Access-Token'));
        $this->assertSame('{"name":"n"}', (string) $req->getBody());
    }

    public function testPostJsonMoneyFieldsKeepJsonNumberWithoutFloatTail(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":{"ok":1},"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);
        $sut = new ShuyunGatewayClient('aid', 'sec', 'http://open-api.test/', $client);

        $old = ini_get('serialize_precision');
        try {
            ini_set('serialize_precision', '17');
            $sut->postJson('shuyun.base.trade.sync', [[
                'payment' => 333.33,
                'post_fee' => 0.01,
                'trade_discount_fee' => 0.03,
                'orders' => [[
                    'price' => 333.33,
                    'discount_fee' => 0.01,
                ]],
            ]]);
        } finally {
            if ($old !== false) {
                ini_set('serialize_precision', (string) $old);
            }
        }

        $body = (string) $container[0]['request']->getBody();
        $this->assertStringContainsString('"payment":333.33', $body);
        $this->assertStringContainsString('"post_fee":0.01', $body);
        $this->assertStringContainsString('"trade_discount_fee":0.03', $body);
        $this->assertStringContainsString('"price":333.33', $body);
        $this->assertStringContainsString('"discount_fee":0.01', $body);
        $this->assertStringNotContainsString('333.32999999999998', $body);
        $this->assertStringNotContainsString('"payment":"333.33"', $body);
        $this->assertStringNotContainsString('"post_fee":"0.01"', $body);
    }

    public function testPutJsonMoneyFieldsKeepJsonNumberWithoutFloatTail(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":{"ok":1},"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test']);
        $sut = new ShuyunGatewayClient('aid', 'sec', 'http://open-api.test/', $client);

        $old = ini_get('serialize_precision');
        try {
            ini_set('serialize_precision', '17');
            $sut->putJson('shuyun.base.trade.sync', [
                'payment' => 333.33,
                'post_fee' => 0.0,
            ], 'tok');
        } finally {
            if ($old !== false) {
                ini_set('serialize_precision', (string) $old);
            }
        }

        $body = (string) $container[0]['request']->getBody();
        $this->assertStringContainsString('"payment":333.33', $body);
        $this->assertStringNotContainsString('333.32999999999998', $body);
        $this->assertStringNotContainsString('"payment":"333.33"', $body);
        $this->assertStringNotContainsString('"post_fee":"0"', $body);
    }
}
