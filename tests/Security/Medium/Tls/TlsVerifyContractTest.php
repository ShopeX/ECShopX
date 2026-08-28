<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Tls;

use CompanysBundle\Ego\UpgradeEgo;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-135,F-136 / TC-04-08
 */
class TlsVerifyContractTest extends TestCase
{
    /**
     * TC-04-08 / F-135：BsPay SDK HTTP 客户端须开启 TLS 证书校验。
     * #given BsPayRequestV2 源码
     * #when 检查 CURLOPT_SSL_VERIFYPEER
     * #then 须为 true
     */
    public function testTc0408BsPayClientEnablesTlsVerification(): void
    {
        #given
        $file = dirname(__DIR__, 4) . '/src/BsPayBundle/Sdk/Core/BsPayRequestV2.php';
        $source = (string) file_get_contents($file);

        #when / #then
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYPEER, true', $source);
        $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYPEER, false', $source);
    }

    /**
     * TC-04-08 / F-136：系统升级下载流须开启 TLS verify。
     * #given UpgradeEgo 下载请求
     * #when 检查 Guzzle verify 选项
     * #then verify 须为 true
     */
    public function testTc0408UpgradeFlowEnablesTlsVerification(): void
    {
        #given
        $body = $this->methodBody(UpgradeEgo::class, 'getDownloadFile');

        #when / #then
        $this->assertStringContainsString("'verify'=>true", str_replace(' ', '', $body));
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
