<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Race;

use KaquanBundle\Services\UserDiscountService;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-151 / TC-04-03
 */
class CouponReceiveConcurrencyTest extends TestCase
{
    /**
     * TC-04-03 / F-151：并发领券须原子校验库存与用户限额（锁/事务契约）。
     * #given UserDiscountService::userGetCard
     * #when 检查并发控制
     * #then 须含领券锁或事务内 FOR UPDATE
     */
    public function testTc0403UserGetCardUsesAtomicStockGuard(): void
    {
        #given
        $body = $this->methodBody(UserDiscountService::class, 'userGetCard');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'CouponReceiveLock',
                'lockCouponReceive',
                'beginTransaction',
                'FOR UPDATE',
                'userGetCardAtomic',
            ]),
            'userGetCard must use atomic lock/transaction for stock and per-user limits'
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
