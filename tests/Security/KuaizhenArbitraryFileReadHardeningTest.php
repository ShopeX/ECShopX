<?php

declare(strict_types=1);

namespace Tests\Security;

use EspierBundle\Support\OutboundUrlAllowlist;
use ThirdPartyBundle\Services\Kuaizhen580Center\Src\DiagnosisService;
use TestCase;

/**
 * ECX-10759：快诊处方 dstFilePath 不得读取本地任意文件。
 */
class KuaizhenArbitraryFileReadHardeningTest extends TestCase
{
    /**
     * @dataProvider dangerousLocalPathsProvider
     */
    public function testOutboundAllowlistRejectsLocalAndDangerousSchemes(string $path): void
    {
        $this->assertFalse(
            OutboundUrlAllowlist::isAllowed($path),
            '本地路径或危险协议不得通过出站白名单: ' . $path
        );
    }

    public function dangerousLocalPathsProvider(): array
    {
        return [
            'absolute passwd' => ['/etc/passwd'],
            'absolute env' => ['/data/httpd/ECShopX/.env'],
            'file scheme' => ['file:///etc/passwd'],
            'phar scheme' => ['phar:///tmp/evil.phar'],
            'php filter' => ['php://filter/convert.base64-encode/resource=/etc/passwd'],
            'proc environ' => ['/proc/self/environ'],
        ];
    }

    public function testPrescriptionAuditFetchesWithRedirectDisabled(): void
    {
        $ref = new \ReflectionMethod(DiagnosisService::class, 'prescriptionMedicationAndAudit');
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $body = implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));

        $this->assertStringContainsString('OutboundUrlAllowlist', $body);
        $this->assertStringContainsString('follow_location', $body);
        $this->assertStringContainsString('stream_context_create', $body);
    }
}
