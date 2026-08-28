<?php

declare(strict_types=1);

namespace Tests\Security;

use MembersBundle\Http\FrontApi\V1\Action\Members;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T12 / F-071 / TC-02-07
 */
class AuthCryptoTc0207ResetPasswordVcodeTest extends TestCase
{
    /**
     * TC-02-07 / AC-02-06：重置密码须校验短信验证码，vcode 不可为 nullable。
     * #given resetMemberPassword 源码
     * #when 检查 validation 规则
     * #then vcode 须为 required
     */
    public function testTc0207ResetPasswordRequiresVcode(): void
    {
        #given
        $body = $this->methodBody(Members::class, 'resetMemberPassword');

        #when / #then
        $this->assertStringContainsString("'vcode' => 'required'", $body);
        $this->assertStringNotContainsString("'vcode' => 'nullable'", $body);
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
