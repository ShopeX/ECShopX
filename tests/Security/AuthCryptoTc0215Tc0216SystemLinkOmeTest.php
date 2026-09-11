<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Http\Request;
use SystemLinkBundle\Middleware\ShopexErpCheck;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T10 / F-042,F-043 / TC-02-15,TC-02-16
 */
class AuthCryptoTc0215Tc0216SystemLinkOmeTest extends TestCase
{
    /**
     * TC-TEST-01 / AC-TEST-01：ome.php 不存在 test 调试路由。
     * #given ome 路由文件
     * #when 检查 test/* 路由注册
     * #then 文件不含 test/event 等调试路由
     */
    public function testTcTest01OmePhpHasNoTestRoutes(): void
    {
        #given
        $routes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/systemlink/ome.php');

        #when / #then
        $this->assertStringNotContainsString(
            "test/event",
            $routes,
            'ome.php must not register test/event debug routes'
        );
        $this->assertStringNotContainsString(
            'BlockErpTestRoutes',
            $routes,
            'ome.php must not register BlockErpTestRoutes test route group'
        );
    }

    /**
     * TC-AUTH-01 / AC-AUTH-01：无 sign 或错 sign 时 ShopexErpCheck 拒绝。
     * #given 无 sign 或错误 sign 的请求
     * #when ShopexErpCheck 处理
     * #then 响应含 sign error
     */
    public function testTcAuth01ShopexErpCheckRejectsMissingOrWrongSign(): void
    {
        #given
        config(['common.oms_token' => 'ome-test-token']);
        $middleware = new ShopexErpCheck();
        $params = ['company_id' => 1, 'method' => 'ome.items.create'];

        $missingSignRequest = Request::create('/systemlink/ome/createitems', 'POST', $params);
        $wrongSign = ShopexErpCheck::gen_sign($params, 'wrong-token');
        $wrongSignRequest = Request::create(
            '/systemlink/ome/createitems',
            'POST',
            array_merge($params, ['sign' => $wrongSign])
        );

        #when
        $missingSignResponse = $middleware->handle(
            $missingSignRequest,
            static fn () => response()->json(['rsp' => 'succ', 'code' => 1])
        );
        $wrongSignResponse = $middleware->handle(
            $wrongSignRequest,
            static fn () => response()->json(['rsp' => 'succ', 'code' => 1])
        );

        #then
        $this->assertSame(200, $missingSignResponse->getStatusCode());
        $missingPayload = json_decode((string) $missingSignResponse->getContent(), true);
        $this->assertSame('sign error', $missingPayload['err_msg'] ?? null);

        $this->assertSame(200, $wrongSignResponse->getStatusCode());
        $wrongPayload = json_decode((string) $wrongSignResponse->getContent(), true);
        $this->assertSame('sign error', $wrongPayload['err_msg'] ?? null);
    }

    /**
     * TC-AUTH-02 / AC-AUTH-02 / TC-TEST-03：SystemLink createItems 须挂 ShopexErpCheck。
     * #given ome 路由文件
     * #when 检查 createitems 路由
     * #then 位于 ShopexErpCheck 中间件组；无未鉴权 GET createitems
     */
    public function testTc0216CreateItemsRouteUsesShopexErpCheck(): void
    {
        #given
        $routes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/systemlink/ome.php');

        #when / #then
        $this->assertStringContainsString("'middleware' => ['ShopexErpCheck']", $routes);
        $this->assertStringContainsString("'ome/createitems'", $routes);
        $this->assertStringNotContainsString(
            "get('ome/createitems'",
            $routes,
            'createItems must not remain on unauthenticated GET route'
        );
    }

    /**
     * TC-TEST-02 / AC-TEST-02：bootstrap/route.php 不再因 test|ome 旁路加载 ome.php。
     * #given route 引导文件
     * #when 检查 test|ome 分发旁路
     * #then 不存在单独加载 ome.php 的 test|ome case
     */
    public function testTcTest02RoutePhpHasNoTestOmeBypass(): void
    {
        #given
        $routeBootstrap = (string) file_get_contents(dirname(__DIR__, 2).'/bootstrap/route.php');

        #when / #then
        $this->assertStringNotContainsString(
            "dingoRoutingKeyOne == 'test' || \$dingoRoutingKeyOne == 'ome'",
            $routeBootstrap,
            'route.php must not load ome.php via test|ome bypass'
        );
    }
}
