<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Http\Controllers\ShuyunOpenPlatformTokenCallbackController;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenCallbackService;
use Psr\Log\LoggerInterface;
use TestCase;

class ShuyunOpenPlatformTokenCallbackControllerTest extends TestCase
{
    private const SECRET = 'mysecret';

    private const TIME = '1690000000000';

    /** @see ShuyunOpenPlatformTokenCallbackServiceTest::GOOD_SIGN */
    private const GOOD_SIGN = '96b3b5d255d9ab923a9e772dd74ff572';

    /** 含 query extra=1 时与 SY-Request-Time 头一起参与验签 */
    private const GOOD_SIGN_WITH_EXTRA_QUERY = '584d088e26557d03dc774a70aada1d84';

    private function credentialRow(): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId(100);
        $e->setAuthValue('tenant-a');
        $e->setAppId('1');
        $e->setAppSecret(self::SECRET);
        $e->setPlatCode(null);

        return $e;
    }

    public function testTokenPassesQueryAndBodyToServiceAndReturnsJson(): void
    {
        $existing = $this->credentialRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with('1')->willReturn($existing);
        $repo->method('findOneByAuthValue')->with('tenant-a')->willReturn($existing);
        $repo->expects($this->once())->method('saveTokenCallbackRowWithRetry')->with($this->callback(function (CompanyShuyunOpenPlatformConfig $e) {
            return $e->getAccessToken() === 'NEWTOK';
        }));

        $service = new ShuyunOpenPlatformTokenCallbackService($repo);
        $this->app->instance(ShuyunOpenPlatformTokenCallbackService::class, $service);

        $body = json_encode([
            [
                'accessToken' => 'NEWTOK',
                'appId' => '1',
                'authType' => '0',
                'authValue' => 'tenant-a',
                'isOverDue' => '0',
            ],
        ], JSON_THROW_ON_ERROR);

        $request = Request::create(
            '/callback?sign='.self::GOOD_SIGN,
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

        $c = new ShuyunOpenPlatformTokenCallbackController();
        $response = $c->token($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"code":200,"msg":"SUCCESS","data":""}',
            $response->getContent()
        );
    }

    public function testDebugLogWritesPayloadThenResultWhenFlagOn(): void
    {
        config(['shuyun_open_platform.callback_debug_log' => true]);

        $captures = [];

        $channelLogger = $this->createMock(LoggerInterface::class);
        $channelLogger->expects($this->exactly(2))->method('info')->willReturnCallback(
            function (string $message, array $context = []) use (&$captures): void {
                $captures[] = [$message, $context];
            }
        );
        $logManager = new class($channelLogger) {
            public function __construct(private LoggerInterface $channelLogger)
            {
            }

            public function channel(string $name): LoggerInterface
            {
                return $this->channelLogger;
            }
        };
        $this->app->instance('log', $logManager);

        $existing = $this->credentialRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with('1')->willReturn($existing);
        $repo->method('findOneByAuthValue')->with('tenant-a')->willReturn($existing);
        $repo->method('saveTokenCallbackRowWithRetry');

        $service = new ShuyunOpenPlatformTokenCallbackService($repo);
        $this->app->instance(ShuyunOpenPlatformTokenCallbackService::class, $service);

        $body = json_encode([
            ['accessToken' => 'TOK', 'appId' => '1', 'authValue' => 'tenant-a'],
        ], JSON_THROW_ON_ERROR);

        $request = Request::create(
            '/third/shuyun/open-platform/callback/token?sign='.self::GOOD_SIGN_WITH_EXTRA_QUERY.'&extra=1',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SY_REQUEST_TIME' => self::TIME,
                'HTTP_X_SHUYUN_TEST' => 'hdr-val',
            ],
            $body
        );

        $c = new ShuyunOpenPlatformTokenCallbackController();
        $c->token($request);

        $this->assertSame('ShuyunOpenPlatform::token_callback::inbound', $captures[0][0]);
        $this->assertSame('/third/shuyun/open-platform/callback/token', $captures[0][1]['path']);
        $this->assertSame('POST', $captures[0][1]['method']);
        $this->assertSame(self::GOOD_SIGN_WITH_EXTRA_QUERY, $captures[0][1]['query']['sign'] ?? null);
        $this->assertSame('1', $captures[0][1]['query']['extra'] ?? null);
        $this->assertArrayHasKey('headers', $captures[0][1]);
        $this->assertSame(self::TIME, $captures[0][1]['headers']['sy-request-time']);
        $this->assertSame($body, $captures[0][1]['body_raw']);

        $this->assertSame('ShuyunOpenPlatform::token_callback::result', $captures[1][0]);
        $this->assertSame(200, $captures[1][1]['code']);
        $this->assertSame('SUCCESS', $captures[1][1]['msg']);
    }
}
