<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Espier;

use EspierBundle\Http\Api\V1\Action\UploadFile;
use EspierBundle\Services\UploadFileService;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-134 / AC-04-01
 */
class EspierUploadErrorFileScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-134：上传错误文件导出须绑定 auth company_id。
     */
    public function testTc0401ExportUploadErrorFileScopesByAuthCompanyId(): void
    {
        #given
        $controllerBody = $this->methodBody(UploadFile::class, 'exportUploadErrorFile');
        $serviceBody = $this->methodBody(UploadFileService::class, 'getErrorFile');

        #when / #then
        $this->assertStringContainsString('company_id', $controllerBody);
        $this->assertStringContainsString('UploadFileTenantScopeGuard', $serviceBody);
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
