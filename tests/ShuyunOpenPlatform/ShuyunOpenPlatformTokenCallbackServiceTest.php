<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Http\Request;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenCallbackService;
use TestCase;

class ShuyunOpenPlatformTokenCallbackServiceTest extends TestCase
{
    private const SECRET = 'mysecret';

    private const TIME = '1690000000000';

    /** MD5(secret + SY-Request-Time + TIME + secret)，无其它 query */
    private const GOOD_SIGN = '96b3b5d255d9ab923a9e772dd74ff572';

    private function credentialRow(string $appId = '1', string $auth = 'tenant-a'): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId(100);
        $e->setAuthValue($auth);
        $e->setAppId($appId);
        $e->setAppSecret(self::SECRET);

        return $e;
    }

    /**
     * @param  array<string, scalar>  $extraQuery
     */
    private function makeRequest(string $body, ?string $sign = null, array $extraQuery = []): Request
    {
        $query = $extraQuery;
        if ($sign !== null) {
            $query['sign'] = $sign;
        }
        $uri = 'http://localhost/cb';
        if ($query !== []) {
            $uri .= '?'.http_build_query($query);
        }

        return Request::create($uri, 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SY_REQUEST_TIME' => self::TIME,
        ], $body);
    }

    private function makeService(CompanyShuyunOpenPlatformConfigRepository $repo): ShuyunOpenPlatformTokenCallbackService
    {
        return new ShuyunOpenPlatformTokenCallbackService($repo);
    }

    public function testPersistsDespiteInvalidSign(): void
    {
        $existing = $this->credentialRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with('1')->willReturn($existing);
        $repo->method('findOneByAuthValue')->with('tenant-a')->willReturn($existing);
        $repo->expects($this->once())->method('saveTokenCallbackRowWithRetry');

        $s = $this->makeService($repo);
        $body = '[{"accessToken":"x","authValue":"tenant-a","appId":"1"}]';
        $req = $this->makeRequest($body, 'badbadbadbadbadbadbadbadbadbad00');
        $out = $s->handle($req);
        $this->assertSame(200, $out['code']);
        $this->assertSame('SUCCESS', $out['msg']);
    }

    public function testEmptyBodySuccessWhenCallbackSecretNotConfigured(): void
    {
        config(['shuyun_open_platform.callback_identity_secret' => '']);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->never())->method('saveTokenCallbackRowWithRetry');

        $s = $this->makeService($repo);
        $out = $s->handle($this->makeRequest('[]', self::GOOD_SIGN));
        $this->assertSame(200, $out['code']);
        $this->assertSame('SUCCESS', $out['msg']);
    }

    public function testPersistsWhenSignMissing(): void
    {
        $existing = $this->credentialRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with('1')->willReturn($existing);
        $repo->method('findOneByAuthValue')->with('tenant-a')->willReturn($existing);
        $repo->expects($this->once())->method('saveTokenCallbackRowWithRetry');

        $s = $this->makeService($repo);
        $body = '[{"accessToken":"x","authValue":"tenant-a","appId":"1"}]';
        $out = $s->handle($this->makeRequest($body, null));
        $this->assertSame(200, $out['code']);
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-CALLBACK-NOPLAT-01 */
    public function testPersistsTokenWhenPlatCodeMissing(): void
    {
        $existing = $this->credentialRow();
        $existing->setPlatCode(null);

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with('1')->willReturn($existing);
        $repo->method('findOneByAuthValue')->with('tenant-a')->willReturn($existing);
        $repo->expects($this->once())->method('saveTokenCallbackRowWithRetry')->with($this->callback(function (CompanyShuyunOpenPlatformConfig $e) {
            return $e->getPlatCode() === null
                && $e->getAccessToken() === 'NEWTOK'
                && $e->getAuthValue() === 'tenant-a';
        }));

        $s = $this->makeService($repo);
        $body = json_encode([
            [
                'accessToken' => 'NEWTOK',
                'appId' => '1',
                'authType' => '0',
                'authValue' => 'tenant-a',
                'isOverDue' => '0',
            ],
        ], JSON_THROW_ON_ERROR);

        $out = $s->handle($this->makeRequest($body, self::GOOD_SIGN));
        $this->assertSame(200, $out['code']);
    }

    /** 库内 appSecret 为空时仍可落库（验签已改用身份注册全局密匙） */
    public function testPersistsWhenDbAppSecretEmpty(): void
    {
        $row = $this->credentialRow();
        $row->setAppSecret(null);
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->willReturn($row);
        $repo->method('findOneByAuthValue')->with('tenant-a')->willReturn($row);
        $repo->expects($this->once())->method('saveTokenCallbackRowWithRetry');

        $s = $this->makeService($repo);
        $out = $s->handle($this->makeRequest(
            '[{"accessToken":"t","authValue":"tenant-a","appId":"1"}]',
            self::GOOD_SIGN
        ));
        $this->assertSame(200, $out['code']);
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-CALLBACK-OK-01 — 同 appId、同 authValue 多条以最后一条为准 */
    public function testMultipleArrayElementsSameAuthLastWins(): void
    {
        $row = $this->credentialRow('99', 'v1');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with('99')->willReturn($row);
        $repo->method('findOneByAuthValue')->with('v1')->willReturn($row);
        $repo->expects($this->exactly(2))->method('saveTokenCallbackRowWithRetry');

        $s = $this->makeService($repo);
        $body = json_encode([
            ['accessToken' => 'a1', 'authValue' => 'v1', 'appId' => '99', 'isOverDue' => '0'],
            ['accessToken' => 'a2', 'authValue' => 'v1', 'appId' => '99', 'isOverDue' => '1'],
        ], JSON_THROW_ON_ERROR);

        $out = $s->handle($this->makeRequest($body, self::GOOD_SIGN));
        $this->assertSame(200, $out['code']);
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-CALLBACK-DB-01 */
    public function testRejectsWhenNoRowForAppId(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with('7')->willReturn(null);
        $repo->expects($this->never())->method('saveTokenCallbackRowWithRetry');

        $s = $this->makeService($repo);
        $body = '[{"accessToken":"t","authValue":"x","appId":"7"}]';
        $out = $s->handle($this->makeRequest($body, self::GOOD_SIGN));
        $this->assertSame(403, $out['code']);
        $this->assertSame('NO_APP_CONFIG', $out['msg']);
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-CALLBACK-DB-03 */
    public function testRejectsInconsistentAppIdInOnePayload(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->never())->method('findOneByAppId');
        $repo->expects($this->never())->method('saveTokenCallbackRowWithRetry');

        $s = $this->makeService($repo);
        $body = json_encode([
            ['accessToken' => 'a', 'authValue' => 'v1', 'appId' => '1'],
            ['accessToken' => 'b', 'authValue' => 'v2', 'appId' => '2'],
        ], JSON_THROW_ON_ERROR);
        $out = $s->handle($this->makeRequest($body, self::GOOD_SIGN));
        $this->assertSame(400, $out['code']);
        $this->assertSame('INCONSISTENT_APP_ID', $out['msg']);
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-PUSH-02 */
    public function testSecondPostPushOverwritesAccessToken(): void
    {
        $row = $this->credentialRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByAppId')->with('1')->willReturn($row);
        $repo->method('findOneByAuthValue')->with('tenant-a')->willReturn($row);
        $repo->expects($this->exactly(2))->method('saveTokenCallbackRowWithRetry');

        $s = $this->makeService($repo);
        $b1 = json_encode([['accessToken' => 'T1', 'authValue' => 'tenant-a', 'appId' => '1']], JSON_THROW_ON_ERROR);
        $b2 = json_encode([['accessToken' => 'T2', 'authValue' => 'tenant-a', 'appId' => '1']], JSON_THROW_ON_ERROR);

        $this->assertSame(200, $s->handle($this->makeRequest($b1, self::GOOD_SIGN))['code']);
        $this->assertSame('T1', $row->getAccessToken());
        $this->assertSame(200, $s->handle($this->makeRequest($b2, self::GOOD_SIGN))['code']);
        $this->assertSame('T2', $row->getAccessToken());
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-CALLBACK-DB-04 */
    public function testRejectsMissingAppIdOnItem(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->expects($this->never())->method('saveTokenCallbackRowWithRetry');
        $s = $this->makeService($repo);
        $out = $s->handle($this->makeRequest(
            '[{"accessToken":"a","authValue":"v1"}]',
            self::GOOD_SIGN
        ));
        $this->assertSame(400, $out['code']);
        $this->assertSame('MISSING_APP_ID', $out['msg']);
    }
}
