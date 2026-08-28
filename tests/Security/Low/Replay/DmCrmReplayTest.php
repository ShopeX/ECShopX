<?php

declare(strict_types=1);

namespace Tests\Security\Low\Replay;

use TestCase;
use ThirdPartyBundle\Services\DmCrm\SubscribeService;

/**
 * codex-security-05-low T40-RED / F-162 / TC-05-07
 */
class DmCrmReplayTest extends TestCase
{
    /**
     * TC-05-07 / F-162：DM CRM 签名回调须防 nonce/timestamp 重放。
     * #given SubscribeService::checkSign
     * #when 检查重放防护
     * #then 须含 nonce 存储与拒绝逻辑
     */
    public function testTc0507DmCrmCheckSignRejectsReplayedCallbacks(): void
    {
        #given
        $body = $this->methodBody(SubscribeService::class, 'checkSign');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'DmCrmCallbackNonceStore',
                'nonce',
                'assertNonceNotReplayed',
                'consumeNonce',
                'replay',
                'timestamp',
            ]),
            'DmCrm checkSign must reject replayed signed callbacks'
        );
        $this->assertTrue(
            preg_match('/nonce|replay|assertNonce/si', $body) === 1,
            'checkSign must implement nonce/replay protection beyond signature verification'
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
