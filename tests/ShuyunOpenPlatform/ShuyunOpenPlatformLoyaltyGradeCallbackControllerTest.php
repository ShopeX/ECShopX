<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Http\Controllers\ShuyunOpenPlatformLoyaltyGradeCallbackController;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use TestCase;

class ShuyunOpenPlatformLoyaltyGradeCallbackControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['shuyun_open_platform.callback_identity_secret' => self::SECRET]);
    }

    private const SECRET = 'mysecret';

    private const SY_TIME = '1690000000000';

    /** 无 URL query、仅头 SY-Request-Time 参与签名时的 MD5（与 ShuyunOfflineBenefitCallbackControllerTest 一致） */
    private const GOOD_SIGN_HEADER_ONLY = '79fee845baa1cd5cc3a01b8da81d18cb';

    public function testReturns200With40001WhenBodyIsInvalidJson(): void
    {
        $controller = new ShuyunOpenPlatformLoyaltyGradeCallbackController();
        $req = Request::create('/cb', 'POST', [], [], [], [], 'not-json');

        $resp = $controller->callback($req);

        $this->assertSame(200, $resp->getStatusCode());
        $decoded = json_decode((string) $resp->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('40001', $decoded['code'] ?? null);
        $this->assertSame('INVALID_JSON', $decoded['msg'] ?? null);
        $this->assertSame('false', $decoded['success'] ?? null);
    }

    public function testReturns200With40304WhenCallbackIdentitySecretNotConfigured(): void
    {
        config(['shuyun_open_platform.callback_identity_secret' => ' ']);
        $cfg = new CompanyShuyunOpenPlatformConfig();
        $cfg->setCompanyId(1);
        $cfg->setPlatCode('X');
        $cfg->setIsEnabled(1);
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findAllEnabledByNormalizedPlatCode')->willReturn([$cfg]);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $body = json_encode(['platCode' => 'X', 'grade' => 1], JSON_THROW_ON_ERROR);
        $req = Request::create('/cb', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $resp = (new ShuyunOpenPlatformLoyaltyGradeCallbackController())->callback($req);

        $this->assertSame(200, $resp->getStatusCode());
        $decoded = json_decode((string) $resp->getContent(), true);
        $this->assertSame('40304', $decoded['code'] ?? null);
        $this->assertSame('CALLBACK_IDENTITY_SECRET_NOT_CONFIGURED', $decoded['msg'] ?? null);
        $this->assertSame('false', $decoded['success'] ?? null);
    }

    public function testReturns200With40301WhenAppIdMissing(): void
    {
        $controller = new ShuyunOpenPlatformLoyaltyGradeCallbackController();
        $req = Request::create('/cb', 'POST', [], [], [], [], '{"grade":1}');

        $resp = $controller->callback($req);

        $this->assertSame(200, $resp->getStatusCode());
        $decoded = json_decode((string) $resp->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('40301', $decoded['code'] ?? null);
        $this->assertSame('APP_ID_REQUIRED', $decoded['msg'] ?? null);
        $this->assertSame('false', $decoded['success'] ?? null);
    }

    public function testReturns200With40302WhenPlatCodeUnknown(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->once())->method('findAllEnabledByNormalizedPlatCode')->with('nope')->willReturn([]);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $body = json_encode(['platCode' => 'nope', 'grade' => 1], JSON_THROW_ON_ERROR);
        $req = Request::create('/cb', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            'HTTP_SY_REQUEST_SIGN' => self::GOOD_SIGN_HEADER_ONLY,
        ], $body);

        $controller = new ShuyunOpenPlatformLoyaltyGradeCallbackController();
        $resp = $controller->callback($req);

        $this->assertSame(200, $resp->getStatusCode());
        $decoded = json_decode((string) $resp->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('40302', $decoded['code'] ?? null);
        $this->assertSame('UNKNOWN_APP', $decoded['msg'] ?? null);
        $this->assertSame('false', $decoded['success'] ?? null);
    }

    public function testReturns200With40307WhenPlatCodeAmbiguous(): void
    {
        $a = new CompanyShuyunOpenPlatformConfig();
        $a->setCompanyId(1);
        $b = new CompanyShuyunOpenPlatformConfig();
        $b->setCompanyId(2);
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->once())->method('findAllEnabledByNormalizedPlatCode')->with('dup')->willReturn([$a, $b]);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $body = json_encode(['platCode' => 'dup', 'grade' => 1], JSON_THROW_ON_ERROR);
        $req = Request::create('/cb', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            'HTTP_SY_REQUEST_SIGN' => self::GOOD_SIGN_HEADER_ONLY,
        ], $body);

        $controller = new ShuyunOpenPlatformLoyaltyGradeCallbackController();
        $resp = $controller->callback($req);

        $this->assertSame(200, $resp->getStatusCode());
        $decoded = json_decode((string) $resp->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('40307', $decoded['code'] ?? null);
        $this->assertSame('AMBIGUOUS_PLAT_CODE', $decoded['msg'] ?? null);
        $this->assertSame('false', $decoded['success'] ?? null);
    }

    public function testReturns40305WhenPlatResolvedButHeaderSignInvalid(): void
    {
        $cfg = new CompanyShuyunOpenPlatformConfig();
        $cfg->setCompanyId(200);
        $cfg->setAppId('app-plat-1');
        $cfg->setPlatCode('NNORMALDTCDEV2');
        $cfg->setAppSecret(self::SECRET);
        $cfg->setIsEnabled(1);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->once())->method('findAllEnabledByNormalizedPlatCode')->with('NNORMALDTCDEV2')->willReturn([$cfg]);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $body = json_encode([
            'platCode' => 'NNORMALDTCDEV2',
            'grade' => '2',
        ], JSON_THROW_ON_ERROR);
        $req = Request::create('/cb', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            'HTTP_SY_REQUEST_SIGN' => 'deadbeefdeadbeefdeadbeefdeadbeef',
        ], $body);

        $controller = new ShuyunOpenPlatformLoyaltyGradeCallbackController();
        $resp = $controller->callback($req);

        $this->assertSame(200, $resp->getStatusCode());
        $decoded = json_decode((string) $resp->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('40305', $decoded['code'] ?? null);
        $this->assertSame('INVALID_SIGN', $decoded['msg'] ?? null);
        $this->assertSame('false', $decoded['success'] ?? null);
    }
}
