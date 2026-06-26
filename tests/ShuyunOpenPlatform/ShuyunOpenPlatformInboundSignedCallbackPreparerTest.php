<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunInboundSignedPrepareMode;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunOpenPlatformInboundSignedCallbackPreparer;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use TestCase;

class ShuyunOpenPlatformInboundSignedCallbackPreparerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['shuyun_open_platform.callback_identity_secret' => 'mysecret']);
    }

    private const SY_TIME = '1690000000000';

    /** {@code md5('mysecret' . 'SY-Request-Time' . SY_TIME . 'mysecret')}，与 {@see ShuyunCallbackSignatureVerifier::verifyHttpCallback} 一致 */
    private const GOOD_SIGN_HEADER_ONLY = '96b3b5d255d9ab923a9e772dd74ff572';

    public function testPrepareResolvesOfflinePlatCodeFromDb(): void
    {
        $cfg = new CompanyShuyunOpenPlatformConfig();
        $cfg->setCompanyId(7);
        $cfg->setPlatCode('OFFLINE');
        $cfg->setIsEnabled(1);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->once())
            ->method('findAllEnabledByNormalizedPlatCode')
            ->with('OFFLINE')
            ->willReturn([$cfg]);

        $preparer = new ShuyunOpenPlatformInboundSignedCallbackPreparer(
            $repo,
            $this->createMock(ShuyunOpenPlatformShopSyncService::class),
            $this->createMock(ShuyunOfflineBenefitRepository::class),
        );

        $body = json_encode(['platCode' => 'OFFLINE', 'grade' => 1], JSON_THROW_ON_ERROR);
        $req = Request::create('/cb', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $req->headers->set('SY-Request-Time', self::SY_TIME);
        $req->headers->set('SY-Request-Sign', self::GOOD_SIGN_HEADER_ONLY);

        $out = $preparer->prepare($req, ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange);

        $this->assertIsArray($out);
        $this->assertSame(7, $out['companyId']);
        $this->assertSame('OFFLINE', $out['body']['platCode'] ?? null);
    }

    public function testPrepareOfflineWithoutFallbackReturnsUnknownAppWhenNotInDb(): void
    {
        config(['shuyun_open_platform.default_plat_code' => '']);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->once())->method('findAllEnabledByNormalizedPlatCode')->with('OFFLINE')->willReturn([]);

        $preparer = new ShuyunOpenPlatformInboundSignedCallbackPreparer(
            $repo,
            $this->createMock(ShuyunOpenPlatformShopSyncService::class),
            $this->createMock(ShuyunOfflineBenefitRepository::class),
        );

        $body = json_encode(['platCode' => 'OFFLINE', 'grade' => 1], JSON_THROW_ON_ERROR);
        $req = Request::create('/cb', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $req->headers->set('SY-Request-Time', self::SY_TIME);
        $req->headers->set('SY-Request-Sign', self::GOOD_SIGN_HEADER_ONLY);

        $out = $preparer->prepare($req, ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange);

        $this->assertInstanceOf(JsonResponse::class, $out);
        $decoded = json_decode((string) $out->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('40302', $decoded['code'] ?? null);
        $this->assertSame('UNKNOWN_APP', $decoded['msg'] ?? null);
    }
}
