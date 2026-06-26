<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefit;
use ShuyunOpenPlatformBundle\Http\Controllers\ShuyunOfflineBenefitCallbackController;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitCallbackService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use TestCase;

class ShuyunOfflineBenefitCallbackControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['shuyun_open_platform.callback_identity_secret' => self::SECRET]);
    }

    private const SECRET = 'mysecret';

    private const SY_TIME = '1690000000000';

    private const APP_ID = '1';

    /** appId + SY-Request-Time（头）参与签名时的 MD5 */
    private const GOOD_SIGN_APP = '79fee845baa1cd5cc3a01b8da81d18cb';

    /**
     * query 无参数、仅头 SY-Request-Time=SY_TIME 参与签名：md5(secret + "SY-Request-Time" + SY_TIME + secret)。
     * （与 appId 在 query 时的 GOOD_SIGN_APP 不同；等级回调里「仅 header」用例往往在验签前就返回，易误用错常量。）
     */
    private const GOOD_SIGN_SY_TIME_ONLY = '96b3b5d255d9ab923a9e772dd74ff572';

    private function tenantConfig(): CompanyShuyunOpenPlatformConfig
    {
        $c = new CompanyShuyunOpenPlatformConfig();
        $c->setCompanyId(100);
        $c->setAppId(self::APP_ID);
        $c->setAppSecret(self::SECRET);

        return $c;
    }

    private function bindConfigRepo(CompanyShuyunOpenPlatformConfig $config): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with(self::APP_ID)->willReturn($config);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);
    }

    private function bindShopSyncEligible(bool $eligible): void
    {
        $shopSync = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopSync->method('isEligible')->willReturn($eligible);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $shopSync);
    }

    public function testMissingAppIdReturns403(): void
    {
        $this->bindConfigRepo($this->tenantConfig());

        // 无 appId/platCode/limitShops/benefitId，无法用影子反查租户 → APP_ID_REQUIRED（验签仅 SY-Request-Time）
        $body = json_encode(['benefitName' => 'n', 'startTime' => '2018-10-01 00:00:00', 'endTime' => '2019-10-01 00:00:00'], JSON_THROW_ON_ERROR);
        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/create?sign='.self::GOOD_SIGN_SY_TIME_ONLY,
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->create($req);
        $this->assertSame(403, $resp->getStatusCode());
        $this->assertStringContainsString('APP_ID_REQUIRED', (string) $resp->getContent());
    }

    public function testSingleSendResolvesTenantByBodyBenefitIdWhenQueryHasNoAppId(): void
    {
        $shadow = new ShuyunOfflineBenefit();
        $shadow->setCompanyId(100);

        $offlineRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $offlineRepo->expects($this->once())->method('findAllByBenefitId')->with('451')->willReturn([$shadow]);

        $cfg = $this->tenantConfig();
        $cfg->setIsEnabled(1);
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->expects($this->once())->method('findOneByCompanyId')->with(100)->willReturn($cfg);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $cfgRepo);
        $this->app->instance(ShuyunOfflineBenefitRepository::class, $offlineRepo);

        $this->bindShopSyncEligible(true);

        $svc = $this->createMock(ShuyunOfflineBenefitCallbackService::class);
        $svc->expects($this->once())->method('singleSend')->with(100, $this->anything())->willReturn([
            'batchId' => '34343434343',
            'benefitCode' => 'STUB-CODE-1',
            'message' => '发送成功',
        ]);
        $this->app->instance(ShuyunOfflineBenefitCallbackService::class, $svc);

        $body = json_encode([
            'requestId' => '34343434343',
            'benefitId' => '451',
            'customerId' => '1121',
            'remark' => '发一张85折优惠券券',
        ], JSON_THROW_ON_ERROR);

        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/single-send',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
                'HTTP_SY_REQUEST_SIGN' => self::GOOD_SIGN_SY_TIME_ONLY,
            ],
            $body
        );

        $resp = (new ShuyunOfflineBenefitCallbackController())->singleSend($req);
        $this->assertSame(200, $resp->getStatusCode());
    }

    public function testCreateResolvesTenantByLimitShopsPlatCodeWithHeaderSign(): void
    {
        $config = $this->tenantConfig();
        $config->setPlatCode('NNORMALDTCDEV2');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->once())->method('findAllEnabledByNormalizedPlatCode')->with('NNORMALDTCDEV2')->willReturn([$config]);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $this->bindShopSyncEligible(true);

        $service = $this->createMock(ShuyunOfflineBenefitCallbackService::class);
        $service->expects($this->once())->method('create')->with(100, $this->callback(function (array $body): bool {
            return isset($body['limitShops'][0]['platCode']) && $body['limitShops'][0]['platCode'] === 'NNORMALDTCDEV2';
        }))->willReturn('8965421');
        $this->app->instance(ShuyunOfflineBenefitCallbackService::class, $service);

        $body = json_encode([
            'clientId' => '435355',
            'benefitId' => '8965421',
            'benefitName' => '85折优惠券',
            'startTime' => '2018-10-01 00:00:00',
            'endTime' => '2019-10-01 00:00:00',
            'limitShops' => [
                ['platCode' => 'NNORMALDTCDEV2', 'shopId' => '76'],
            ],
        ], JSON_THROW_ON_ERROR);

        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/create',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
                'HTTP_SY_REQUEST_SIGN' => self::GOOD_SIGN_SY_TIME_ONLY,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->create($req);
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"code":10000,"message":"","data":{"benefitId":"8965421"}}',
            $resp->getContent()
        );
    }

    public function testCreateReturns403WhenPlatCodeFromLimitShopsIsAmbiguous(): void
    {
        $a = $this->tenantConfig();
        $a->setCompanyId(1);
        $a->setPlatCode('NNORMALDTCDEV2');
        $b = $this->tenantConfig();
        $b->setCompanyId(2);
        $b->setPlatCode('NNORMALDTCDEV2');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->once())->method('findAllEnabledByNormalizedPlatCode')->with('NNORMALDTCDEV2')->willReturn([$a, $b]);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $body = json_encode([
            'limitShops' => [['platCode' => 'NNORMALDTCDEV2', 'shopId' => '76']],
        ], JSON_THROW_ON_ERROR);

        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/create',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
                'HTTP_SY_REQUEST_SIGN' => self::GOOD_SIGN_SY_TIME_ONLY,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->create($req);
        $this->assertSame(403, $resp->getStatusCode());
        $this->assertStringContainsString('AMBIGUOUS_PLAT_CODE', (string) $resp->getContent());
    }

    public function testInvalidSignReturns403(): void
    {
        $this->bindConfigRepo($this->tenantConfig());

        $body = json_encode(['benefitId' => 'b1', 'benefitName' => 'n', 'startTime' => '2018-10-01 00:00:00', 'endTime' => '2019-10-01 00:00:00'], JSON_THROW_ON_ERROR);
        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/create?appId='.self::APP_ID.'&sign=deadbeefdeadbeefdeadbeefdeadbeef',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->create($req);
        $this->assertSame(403, $resp->getStatusCode());
        $this->assertStringContainsString('INVALID_SIGN', (string) $resp->getContent());
    }

    public function testCreateReturns403WhenTenantNotEligible(): void
    {
        $this->bindConfigRepo($this->tenantConfig());
        $this->bindShopSyncEligible(false);

        $service = $this->createMock(ShuyunOfflineBenefitCallbackService::class);
        $service->expects($this->never())->method('create');
        $this->app->instance(ShuyunOfflineBenefitCallbackService::class, $service);

        $body = json_encode([
            'benefitId' => '8965421',
            'benefitName' => '双11',
            'startTime' => '2018-10-01 00:00:00',
            'endTime' => '2019-10-01 00:00:00',
        ], JSON_THROW_ON_ERROR);

        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/create?appId='.self::APP_ID.'&sign='.self::GOOD_SIGN_APP,
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->create($req);
        $this->assertSame(403, $resp->getStatusCode());
        $this->assertStringContainsString('SHUYUN_OFFLINE_BENEFIT_NOT_ELIGIBLE', (string) $resp->getContent());
    }

    public function testCreateReturns200WhenSignedAndServiceSucceeds(): void
    {
        $this->bindConfigRepo($this->tenantConfig());
        $this->bindShopSyncEligible(true);

        $service = $this->createMock(ShuyunOfflineBenefitCallbackService::class);
        $service->expects($this->once())->method('create')->with(100, $this->callback(function (array $body): bool {
            return ($body['benefitName'] ?? '') === '双11';
        }))->willReturn('8965421');
        $this->app->instance(ShuyunOfflineBenefitCallbackService::class, $service);

        $body = json_encode([
            'benefitName' => '双11',
            'startTime' => '2018-10-01 00:00:00',
            'endTime' => '2019-10-01 00:00:00',
        ], JSON_THROW_ON_ERROR);

        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/create?appId='.self::APP_ID.'&sign='.self::GOOD_SIGN_APP,
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->create($req);
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"code":10000,"message":"","data":{"benefitId":"8965421"}}',
            $resp->getContent()
        );
    }

    public function testSingleSendReturns10000WithBatchIdAndBenefitCodeFields(): void
    {
        $this->bindConfigRepo($this->tenantConfig());
        $this->bindShopSyncEligible(true);

        $service = $this->createMock(ShuyunOfflineBenefitCallbackService::class);
        $service->expects($this->once())->method('singleSend')->with(100, $this->callback(function (array $body): bool {
            return $body['requestId'] === 'req-1' && $body['benefitId'] === '8965421';
        }))->willReturn([
            'batchId' => 'req-1',
            'benefitCode' => '8976680',
            'message' => '发送成功',
        ]);
        $this->app->instance(ShuyunOfflineBenefitCallbackService::class, $service);

        $body = json_encode([
            'requestId' => 'req-1',
            'benefitId' => '8965421',
            'customerId' => '7895642',
        ], JSON_THROW_ON_ERROR);

        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/single-send?appId='.self::APP_ID.'&sign='.self::GOOD_SIGN_APP,
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->singleSend($req);
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"code":10000,"message":"发送成功","data":{"batchId":"req-1","benefitCode":"8976680"}}',
            $resp->getContent()
        );
    }

    public function testSingleSendReturns50001WhenServiceReturnsFailureMessage(): void
    {
        $this->bindConfigRepo($this->tenantConfig());
        $this->bindShopSyncEligible(true);

        $service = $this->createMock(ShuyunOfflineBenefitCallbackService::class);
        $service->expects($this->once())->method('singleSend')->willReturn([
            'batchId' => 'req-1',
            'benefitCode' => '',
            'message' => '库存不足',
        ]);
        $this->app->instance(ShuyunOfflineBenefitCallbackService::class, $service);

        $body = json_encode([
            'requestId' => 'req-1',
            'benefitId' => '8965421',
            'customerId' => '7895642',
        ], JSON_THROW_ON_ERROR);

        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/single-send?appId='.self::APP_ID.'&sign='.self::GOOD_SIGN_APP,
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->singleSend($req);
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"code":50001,"message":"库存不足","data":{"batchId":"req-1","benefitCode":""}}',
            $resp->getContent()
        );
    }

    public function testBatchSendReturns10000WithBatchIdOnly(): void
    {
        $this->bindConfigRepo($this->tenantConfig());
        $this->bindShopSyncEligible(true);

        $service = $this->createMock(ShuyunOfflineBenefitCallbackService::class);
        $service->expects($this->once())->method('batchSend')->with(100, $this->callback(function (array $body): bool {
            return $body['requestId'] === 'batch-req-1' && $body['benefitId'] === '8965421';
        }))->willReturn([
            'batchId' => 'batch-req-1',
            'message' => '异步批量发放处理中，请稍后重试或依赖数云明细推送获取结果',
        ]);
        $this->app->instance(ShuyunOfflineBenefitCallbackService::class, $service);

        $body = json_encode([
            'requestId' => 'batch-req-1',
            'benefitId' => '8965421',
            'customerList' => ['7895642'],
        ], JSON_THROW_ON_ERROR);

        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/batch-send?appId='.self::APP_ID.'&sign='.self::GOOD_SIGN_APP,
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->batchSend($req);
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"code":10000,"message":"异步批量发放处理中，请稍后重试或依赖数云明细推送获取结果","data":{"batchId":"batch-req-1"}}',
            $resp->getContent()
        );
    }

    public function testCreateReturns422WhenServiceThrowsInvalidArgument(): void
    {
        $this->bindConfigRepo($this->tenantConfig());
        $this->bindShopSyncEligible(true);

        $service = $this->createMock(ShuyunOfflineBenefitCallbackService::class);
        $service->expects($this->once())->method('create')->willThrowException(new \InvalidArgumentException('NO_MATCHING_COUPON_TEMPLATE'));
        $this->app->instance(ShuyunOfflineBenefitCallbackService::class, $service);

        $body = json_encode([], JSON_THROW_ON_ERROR);
        $req = Request::create(
            '/third/shuyun/open-platform/callback/offline-benefit/create?appId='.self::APP_ID.'&sign='.self::GOOD_SIGN_APP,
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::SY_TIME,
            ],
            $body
        );

        $c = new ShuyunOfflineBenefitCallbackController();
        $resp = $c->create($req);
        $this->assertSame(422, $resp->getStatusCode());
        $this->assertStringContainsString('NO_MATCHING_COUPON_TEMPLATE', (string) $resp->getContent());
    }
}
