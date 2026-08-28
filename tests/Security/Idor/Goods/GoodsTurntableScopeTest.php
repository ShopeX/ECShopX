<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Goods;

use PromotionsBundle\Http\Api\V1\Action\Turntable;
use TestCase;

/**
 * codex-security-03-high-idor T24 / F-032,F-033 / TC-03-09
 */
class GoodsTurntableScopeTest extends TestCase
{
    /**
     * TC-03-09 / F-033：大转盘日志读取须校验 activity 归属 auth company。
     * #given getLogStatistics 源码
     * #when 检查活动归属
     * #then 须调用 PromotionActivityTenantScopeGuard
     */
    public function testTc0309TurntableLogScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Turntable::class, 'getLogStatistics');

        #when / #then
        $this->assertStringContainsString('PromotionActivityTenantScopeGuard', $body);
    }

    /**
     * TC-03-09 / F-032：大转盘日志导出须校验 activity 归属 auth company。
     * #given exportLog 源码
     * #when 检查活动归属
     * #then 须调用 PromotionActivityTenantScopeGuard
     */
    public function testTc0309TurntableExportScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Turntable::class, 'exportLog');

        #when / #then
        $this->assertStringContainsString('PromotionActivityTenantScopeGuard', $body);
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
