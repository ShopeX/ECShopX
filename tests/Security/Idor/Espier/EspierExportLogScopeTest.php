<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Espier;

use EspierBundle\Http\Api\V1\Action\ExportLogController;
use TestCase;

/**
 * codex-security-03-high-idor T26 / F-018 / TC-03-07
 */
class EspierExportLogScopeTest extends TestCase
{
    /**
     * TC-03-07 / F-018：导出文件下载须校验 log 归属 auth company。
     * #given fileDown 源码
     * #when 检查租户绑定
     * #then 须调用 ExportLogTenantScopeGuard
     */
    public function testTc0307ExportLogDownloadScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(ExportLogController::class, 'fileDown');

        #when / #then
        $this->assertStringContainsString('ExportLogTenantScopeGuard', $body);
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
