<?php

declare(strict_types=1);

namespace Tests\Security;

use EspierBundle\Middleware\BlockErpTestRoutesMiddleware;
use Illuminate\Http\Request;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T10 / F-040 / TC-02-01
 */
class AuthCryptoTc0201SaasErpTestEndpointTest extends TestCase
{
    /**
     * TC-02-01 / AC-02-01：SaaS ERP test 路由须在 routes/thirdparty/saaserp.php 挂防护，非 SystemLink ome。
     * #given saaserp 路由文件
     * #when 检查 test/event 路由组
     * #then 须含 BlockErpTestRoutes 中间件
     */
    public function testTc0201SaasErpTestRoutesUseBlockMiddleware(): void
    {
        #given
        $routes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/thirdparty/saaserp.php');
        $group = $this->extractGroupBlockContaining($routes, 'saaserp/test/event');

        #when / #then
        $this->assertStringContainsString(
            'BlockErpTestRoutes',
            $group,
            'SaaS ERP test routes must use BlockErpTestRoutes middleware'
        );
        $this->assertStringContainsString(
            'saaserp/test/event',
            $group,
            'Group must contain saaserp/test/event route (not SystemLink ome test/event)'
        );
    }

    /**
     * TC-02-01 / AC-02-01：生产环境未认证访问 SaaS ERP test 须 403。
     * #given APP_ENV=production
     * #when 请求经 BlockErpTestRoutes 中间件
     * #then 返回 403
     */
    public function testTc0201SaasErpTestEventBlockedInProduction(): void
    {
        #given
        $previous = env('APP_ENV');
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';

        $middleware = new BlockErpTestRoutesMiddleware();
        $request = Request::create('/saaserp/test/event/12345', 'GET');

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
