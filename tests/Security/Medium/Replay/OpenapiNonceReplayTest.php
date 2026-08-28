<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Replay;

use OpenapiBundle\Middleware\OpenapiCheck;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-072 / TC-04-06
 */
class OpenapiNonceReplayTest extends TestCase
{
    /**
     * TC-04-06 / F-072：OpenAPI 签名请求须防 nonce 重放。
     * #given OpenapiCheck::handle
     * #when 检查重放防护
     * #then 须含 nonce/request-id 存储与拒绝逻辑
     */
    public function testTc0406OpenapiCheckRejectsReplayedNonce(): void
    {
        #given
        $body = $this->methodBody(OpenapiCheck::class, 'handle');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'OpenapiNonceStore',
                'nonce',
                'request_id',
                'assertNonceNotReplayed',
                'consumeNonce',
            ]),
            'OpenapiCheck must reject replayed signed requests via nonce store'
        );
    }

    /**
     * @param string[] $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
