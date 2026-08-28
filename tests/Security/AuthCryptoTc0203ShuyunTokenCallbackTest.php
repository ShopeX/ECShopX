<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Http\Controllers\ShuyunOpenPlatformTokenCallbackController;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenCallbackService;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T10 / F-041 / TC-02-03
 */
class AuthCryptoTc0203ShuyunTokenCallbackTest extends TestCase
{
    private const SECRET = 'mysecret';

    private const TIME = '1690000000000';

    protected function setUp(): void
    {
        parent::setUp();
        config(['shuyun_open_platform.callback_identity_secret' => self::SECRET]);
    }

    /**
     * TC-02-03 / AC-02-02：数云 token 无有效签名须拒绝写凭证。
     * #given 已配置 callback_identity_secret 与合法租户
     * #when token 回调带错误 sign
     * #then 403 且不调用 saveTokenCallbackRowWithRetry
     */
    public function testTc0203TokenCallbackRejectsInvalidSign(): void
    {
        #given
        $existing = new CompanyShuyunOpenPlatformConfig();
        $existing->setCompanyId(100);
        $existing->setAppId('1');
        $existing->setAuthValue('tenant-a');
        $existing->setAppSecret(self::SECRET);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->willReturn($existing);
        $repo->expects($this->never())->method('saveTokenCallbackRowWithRetry');
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);
        $this->app->instance(
            ShuyunOpenPlatformTokenCallbackService::class,
            new ShuyunOpenPlatformTokenCallbackService($repo)
        );

        $body = json_encode([
            ['accessToken' => 'NEWTOK', 'appId' => '1', 'authValue' => 'tenant-a'],
        ], JSON_THROW_ON_ERROR);

        $request = Request::create(
            '/callback/token?sign=deadbeefdeadbeefdeadbeefdeadbeef',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::TIME,
            ],
            $body
        );

        #when
        $response = (new ShuyunOpenPlatformTokenCallbackController())->token($request);

        #then
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('INVALID_SIGN', (string) $response->getContent());
    }
}
