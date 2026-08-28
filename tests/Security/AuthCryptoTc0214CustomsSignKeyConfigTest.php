<?php

declare(strict_types=1);

namespace Tests\Security;

use TestCase;
use ThirdPartyBundle\Services\CustomsCentre\CustomsService;

/**
 * codex-security-02-high-auth-crypto T12 / F-064 / TC-02-14
 */
class AuthCryptoTc0214CustomsSignKeyConfigTest extends TestCase
{
    /**
     * TC-02-14 / AC-02-13：海关签名密钥不得硬编码，须从配置读取。
     * #given CustomsService 源码
     * #when 检查 signKey 来源
     * #then 不得含硬编码 U2FsdGVkX11BC2，须使用 config
     */
    public function testTc0214CustomsSignKeyLoadedFromConfig(): void
    {
        #given
        $file = (new \ReflectionClass(CustomsService::class))->getFileName();
        $this->assertNotFalse($file);
        $source = (string) file_get_contents($file);

        #when / #then
        $this->assertStringNotContainsString("private static \$signKey = 'U2FsdGVkX11BC2'", $source);
        $this->assertStringContainsString("config('customs.sign_key')", $source);
    }
}
