<?php

declare(strict_types=1);

namespace Tests\Security;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\SocialTrustLoginService;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T13 / F-039 / TC-02-10
 */
class AuthCryptoTc0210AppleOAuthStateTest extends TestCase
{
    private const LEGITIMATE_H5 = 'https://shop.example.com';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:test-apple-oauth-state-signing-key',
            'common.h5_base_url' => self::LEGITIMATE_H5,
        ]);
    }

    /**
     * TC-02-10 / AC-02-09：伪造 state 含攻击 Origin 时不得信任。
     * #given 合法 h5_base_url 与攻击者伪造的未签名 state
     * #when resolveAppleCallbackH5Host
     * #then 不得返回攻击 Origin
     */
    public function testTc0210ForgedStateWithEvilOriginIsRejected(): void
    {
        #given
        $evilHost = 'https://evil.attacker.com';
        $forgedPayload = json_encode(['h5_host' => $evilHost], JSON_UNESCAPED_SLASHES);
        $forgedState = rtrim(strtr(base64_encode((string) $forgedPayload), '+/', '-_'), '=');
        $service = new SocialTrustLoginService();

        #when
        $resolved = $service->resolveAppleCallbackH5Host([
            'h5_host' => '',
            'state' => $forgedState,
        ]);

        #then
        $this->assertNotSame($evilHost, $resolved);
        $this->assertSame(self::LEGITIMATE_H5, $resolved);
    }

    /**
     * TC-02-10 / AC-02-09：请求参数 h5_host 含攻击 Origin 时不得信任。
     * #given 攻击者直接传入 evil h5_host
     * #when resolveAppleCallbackH5Host
     * #then 回退到合法 h5_base_url
     */
    public function testTc0210EvilRequestH5HostIsRejected(): void
    {
        #given
        $evilHost = 'https://evil.attacker.com';
        $service = new SocialTrustLoginService();

        #when
        $resolved = $service->resolveAppleCallbackH5Host([
            'h5_host' => $evilHost,
            'state' => '',
        ]);

        #then
        $this->assertNotSame($evilHost, $resolved);
        $this->assertSame(self::LEGITIMATE_H5, $resolved);
    }

    /**
     * TC-02-10 / AC-02-09：合法 Origin 须生成可验证的签名 state。
     * #given 白名单内 h5_host
     * #when encodeAppleOAuthState 后 decodeAppleOAuthState
     * #then 还原 h5_host；篡改签名后解码失败
     */
    public function testTc0210SignedStateRoundTripAndTamperRejected(): void
    {
        #given
        $service = new SocialTrustLoginService();
        $state = $service->encodeAppleOAuthState(self::LEGITIMATE_H5);

        #when
        $decoded = $service->decodeAppleOAuthState($state);
        $tampered = $state . 'x';

        #then
        $this->assertSame(self::LEGITIMATE_H5, $decoded['h5_host'] ?? '');
        $this->assertSame([], $service->decodeAppleOAuthState($tampered));
    }

    /**
     * TC-02-10 / AC-02-09：非白名单 Origin 不得写入 state。
     * #given 不在白名单的 h5_host
     * #when encodeAppleOAuthState
     * #then 抛出 ResourceException
     */
    public function testTc0210EncodeRejectsNonAllowlistedOrigin(): void
    {
        #given
        $service = new SocialTrustLoginService();

        #when / #then
        $this->expectException(ResourceException::class);
        $service->encodeAppleOAuthState('https://evil.attacker.com');
    }
}
