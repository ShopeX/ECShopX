<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Auth;

use KaquanBundle\Http\AdminApi\V1\Action\UserDiscount;
use TestCase;
use ThirdPartyBundle\Http\ThirdApi\V1\Action\Customs;
use ThirdPartyBundle\Http\ThirdApi\V1\Action\ShansongCallback;
use ThirdPartyBundle\Middleware\DadaApiCheck;

/**
 * codex-security-04-medium T31-RED / TC-04-02,02b,02c
 */
class MediumUnsignedCallbackAuthTest extends TestCase
{
    /**
     * TC-04-02 / F-140,F-143：山松/海关回调须校验签名或认证中间件。
     * #given ShansongCallback 与 Customs@updateOrderData
     * #when 检查入口认证
     * #then 须含签名验证或 inbound callback verifier
     */
    public function testTc0402UnsignedCallbacksRequireSignatureVerification(): void
    {
        #given
        $shansongBody = $this->methodBody(ShansongCallback::class, 'updateOrderStatus');
        $customsBody = $this->methodBody(Customs::class, 'updateOrderData');

        #when / #then
        $this->assertTrue(
            $this->containsAny($shansongBody, ['InboundSignedCallback', 'verifyCallbackSignature', 'ShansongCallbackSignature']),
            'Shansong callback must verify inbound signature'
        );
        $this->assertTrue(
            $this->containsAny($customsBody, ['verifyCustomsCallbackSignature', 'InboundSignedCallback', 'CustomsCallbackSignatureVerifier']),
            'Customs response callback must verify signature'
        );
    }

    /**
     * TC-04-02b / F-137：达达回调签名须使用共享密钥。
     * #given DadaApiCheck::_sign
     * #when 检查签名材料
     * #then 须从配置读取 app_secret 等共享密钥
     */
    public function testTc0402bDadaCallbackSignUsesSharedSecret(): void
    {
        #given
        $body = $this->methodBody(DadaApiCheck::class, '_sign');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, ["config('dada.", "config('local_delivery.dada", 'app_secret', 'shared_secret']),
            'Dada callback signature must include tenant/shared secret'
        );
    }

    /**
     * TC-04-02c / F-144：导购发券须校验目标用户归属 scope。
     * #given UserDiscount@giveUserCoupons
     * #when 检查用户授权
     * #then 须调用 SalespersonMemberScopeGuard
     */
    public function testTc0402cSalespersonGiveCouponsEnforcesMemberScope(): void
    {
        #given
        $body = $this->methodBody(UserDiscount::class, 'giveUserCoupons');

        #when / #then
        $this->assertStringContainsString('SalespersonMemberScopeGuard', $body);
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
