<?php

declare(strict_types=1);

namespace Tests\Security;

use HfPayBundle\Http\ThirdApi\V1\Action\HfPay;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T13 / F-035 / TC-02-11
 */
class AuthCryptoTc0211HfPayCertificateChainTest extends TestCase
{
    /**
     * TC-02-11 / AC-02-10：汇付 notify 须校验 PKCS#7 证书链到信任锚。
     * #given HfPay::notify 源码
     * #when 检查证书链校验逻辑
     * #then 须调用 verifyCertificat 且配置信任锚
     */
    public function testTc0211NotifyVerifiesCertificateChain(): void
    {
        #given
        $body = $this->methodBody(HfPay::class, 'notify');

        #when / #then
        $this->assertStringContainsString('verifyCertificat', $body);
        $this->assertStringContainsString('strTrustedCACertFilePath', $body);
        $this->assertStringContainsString('strMsgP7AttachedSignCertContent', $body);
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
