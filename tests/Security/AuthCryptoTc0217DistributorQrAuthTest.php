<?php

declare(strict_types=1);

namespace Tests\Security;

use TestCase;

/**
 * codex-security-02-high-auth-crypto T10 / F-045 / TC-02-17
 */
class AuthCryptoTc0217DistributorQrAuthTest extends TestCase
{
    /**
     * TC-02-17 / AC-02-01：distributor QR 路由须挂 api.auth。
     * #given routes/admin/distributor.php
     * #when 检查 bydistributor/salespersonQrcode 路由组
     * #then 须含 api.auth 中间件
     */
    public function testTc0217DistributorQrRoutesRequireApiAuth(): void
    {
        #given
        $routes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/admin/distributor.php');

        #when / #then
        $this->assertStringContainsString(
            'bydistributor/salespersonQrcode',
            $routes,
            'Distributor QR route must exist'
        );

        foreach (['/bydistributor/salespersonQrcode', '/wxapp/bydistributor/salespersonQrcode'] as $path) {
            $group = $this->extractGroupBlockContaining($routes, $path);
            $this->assertStringContainsString(
                'api.auth',
                $group,
                "Route group for {$path} must require api.auth"
            );
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
