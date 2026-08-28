<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Payment;

use AliBundle\Services\AliMiniAppSettingService;
use TestCase;

/**
 * codex-security-03-high-idor T21 / F-011 / TC-03-13
 */
class IdorPaymentTc0313AliMiniAppSettingTest extends TestCase
{
    /**
     * TC-03-13 / AC-03-01：Ali 小程序 save 须拒绝跨租户 setting_id 覆盖。
     * #given save 源码
     * #when 检查 setting_id 归属
     * #then 更新前须校验 setting 属于当前 company_id
     */
    public function testTc0313AliMiniAppSaveRejectsForeignSettingId(): void
    {
        #given
        $body = $this->methodBody(AliMiniAppSettingService::class, 'save');

        #when / #then
        $this->assertStringContainsString('setting_id', $body);
        $this->assertStringContainsString('company_id', $body);
        $this->assertMatchesRegularExpression(
            '/无权|没有该小程序的配置权限|assertCompanyScope|getInfo\(\[\'setting_id\'\]/',
            $body
        );
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
