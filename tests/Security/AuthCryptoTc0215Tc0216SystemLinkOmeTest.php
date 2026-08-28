<?php

declare(strict_types=1);

namespace Tests\Security;

use EspierBundle\Middleware\BlockErpTestRoutesMiddleware;
use Illuminate\Http\Request;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T10 / F-042,F-043 / TC-02-15,TC-02-16
 */
class AuthCryptoTc0215Tc0216SystemLinkOmeTest extends TestCase
{
    /**
     * TC-02-15 / AC-02-01：SystemLink 完成订单 test 路由须防护。
     * #given ome 路由文件
     * #when 检查 test/event
     * #then 挂 BlockErpTestRoutes
     */
    public function testTc0215SystemLinkOrderTestRouteUsesBlockMiddleware(): void
    {
        #given
        $routes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/systemlink/ome.php');
        $group = $this->extractGroupBlockContaining($routes, "test/event/{order_id}");

        #when / #then
        $this->assertStringContainsString('BlockErpTestRoutes', $group);
    }

    /**
     * TC-02-16 / AC-02-01：SystemLink createItems 须挂 ShopexErpCheck。
     * #given ome 路由文件
     * #when 检查 createitems 路由
     * #then 位于 ShopexErpCheck 中间件组
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
     * TC-02-15 / AC-02-01：生产环境 SystemLink test/event 须 403。
     * #given APP_ENV=production
     * #when BlockErpTestRoutes 处理 test/event
     * #then 403
     */
    public function testTc0215SystemLinkTestEventBlockedInProduction(): void
    {
        #given
        $previous = env('APP_ENV');
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';

        $middleware = new BlockErpTestRoutesMiddleware();
        $request = Request::create('/test/event/999', 'GET');

        try {
            #when
            $response = $middleware->handle($request, static fn () => response('ok', 200));

            #then
            $this->assertSame(403, $response->getStatusCode());
        } finally {
            if ($previous === null) {
                putenv('APP_ENV');
                unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
            } else {
                putenv('APP_ENV='.$previous);
                $_ENV['APP_ENV'] = $previous;
                $_SERVER['APP_ENV'] = $previous;
            }
        }
    }

    private function extractGroupBlockContaining(string $content, string $needle): string
    {
        $needlePos = strpos($content, $needle);
        $this->assertNotFalse($needlePos, "Route content containing {$needle} must exist");

        $groupStart = strrpos(substr($content, 0, $needlePos), '$api->group');
        $this->assertNotFalse($groupStart, 'Surrounding group declaration must exist');

        $functionPos = strpos($content, 'function', $groupStart);
        $this->assertNotFalse($functionPos, 'Group callback must exist');

        $openBrace = strpos($content, '{', $functionPos);
        $this->assertNotFalse($openBrace, 'Group body must exist');

        $depth = 0;
        $length = strlen($content);
        for ($i = $openBrace; $i < $length; $i++) {
            $char = $content[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $groupStart, $i - $groupStart + 1);
                }
            }
        }

        $this->fail('Group block must be closed');
    }
}
