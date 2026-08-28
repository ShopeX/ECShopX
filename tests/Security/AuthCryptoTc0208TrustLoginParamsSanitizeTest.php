<?php

declare(strict_types=1);

namespace Tests\Security;

use MembersBundle\Http\FrontApi\V1\Action\TrustLogin;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T12 / F-066 / TC-02-08
 */
class AuthCryptoTc0208TrustLoginParamsSanitizeTest extends TestCase
{
    /**
     * TC-02-08 / AC-02-07：公开 trust-login params 须经 sanitizeConfigRow 脱敏，不得泄露 secret。
     * #given getTrustLoginParams 源码
     * #when 检查 config_info 返回前处理
     * #then 须调用 sanitizeConfigRow
     */
    public function testTc0208TrustLoginParamsSanitizesConfigInfo(): void
    {
        #given
        $body = $this->methodBody(TrustLogin::class, 'getTrustLoginParams');

        #when / #then
        $this->assertStringContainsString('sanitizeConfigRow', $body);
        $this->assertStringContainsString("config_info", $body);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }
}
