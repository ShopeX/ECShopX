<?php

declare(strict_types=1);

namespace Tests\EspierBundle;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response\Factory;
use EspierBundle\Http\FrontApi\V1\Action\UploadFile;
use EspierBundle\Interfaces\UploadTokenInterface;
use Illuminate\Http\Request;
use Mockery;

/**
 * UploadFile::getPicUploadToken — TC-01, TC-02, TC-03, TC-05（RED/GREEN）
 * 计划：.tasks/plans/merchant-h5-image-upload-token.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class UploadFileGetPicUploadTokenTest extends \TestCase
{
    private const COMPANY_ID = 1;

    /**
     * 最小 Lumen 容器，避免 bootstrap/app.php 触发 Doctrine DB 连接。
     */
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();
        $app->instance('path.lang', $app->basePath('resources/lang'));
        $app->register(\Illuminate\Translation\TranslationServiceProvider::class);
        $app->register(\Illuminate\Validation\ValidationServiceProvider::class);

        return $app;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-01 / A2：auth 仅 company_id+account_id → 限流 key 使用 account_id，发 token 成功。
     * #given auth 无 user_id，仅有 company_id 与 account_id
     * #when getPicUploadToken
     * #then Redis key 为 uploadImageNum:{company_id}:{account_id}，factory mock 返回 token
     */
    public function testTc01RateLimitKeyUsesAccountIdWhenUserIdMissing(): void
    {
        $accountId = 9001;
        $expectedKey = 'uploadImageNum:' . self::COMPANY_ID . ':' . $accountId;

        $this->bindResponseFactory();
        $capturedGetKey = null;
        $capturedIncrKey = null;
        $this->bindRedisMock($capturedGetKey, $capturedIncrKey);
        $this->bindUploadTokenFactory($this->sampleTokenPayload());

        $request = $this->createRequest([
            'company_id' => self::COMPANY_ID,
            'account_id' => $accountId,
        ], [
            'filetype' => 'image',
            'filename' => 'license.jpg',
            'group' => 'merchant',
        ]);

        $controller = new UploadFile();
        $result = $controller->getPicUploadToken($request);

        $this->assertSame($expectedKey, $capturedGetKey, 'TC-01: Redis get should use account_id in rate-limit key');
        $this->assertSame($expectedKey, $capturedIncrKey, 'TC-01: Redis incr should use account_id in rate-limit key');
        $this->assertSame('mock-upload-token', $result['token']);
    }

    /**
     * TC-02 / A3：auth 含 user_id（可同时有 account_id）→ 限流 key 优先 user_id。
     * #given auth 同时含 user_id 与 account_id
     * #when getPicUploadToken
     * #then Redis key 为 uploadImageNum:{company_id}:{user_id}，不误用 account_id
     */
    public function testTc02RateLimitKeyPrefersUserIdOverAccountId(): void
    {
        $userId = 200;
        $accountId = 9001;
        $expectedKey = 'uploadImageNum:' . self::COMPANY_ID . ':' . $userId;

        $this->bindResponseFactory();
        $capturedGetKey = null;
        $capturedIncrKey = null;
        $this->bindRedisMock($capturedGetKey, $capturedIncrKey);
        $this->bindUploadTokenFactory($this->sampleTokenPayload());

        $request = $this->createRequest([
            'company_id' => self::COMPANY_ID,
            'user_id' => $userId,
            'account_id' => $accountId,
        ], [
            'filetype' => 'image',
            'filename' => 'license.jpg',
            'group' => 'merchant',
        ]);

        $controller = new UploadFile();
        $controller->getPicUploadToken($request);

        $this->assertSame($expectedKey, $capturedGetKey, 'TC-02: Redis get should prefer user_id over account_id');
        $this->assertSame($expectedKey, $capturedIncrKey, 'TC-02: Redis incr should prefer user_id over account_id');
    }

    /**
     * TC-03 / A4：auth 仅有 company_id，无 user_id/account_id → 抛业务异常，禁止空后缀 key。
     * #given auth 仅 company_id
     * #when getPicUploadToken
     * #then 抛 ResourceException，Redis 不被调用
     */
    public function testTc03MissingUserAndAccountIdThrowsResourceException(): void
    {
        $this->bindResponseFactory();
        $this->bindRedisNeverCalled();
        $this->bindUploadTokenFactoryNeverCalled();

        $request = $this->createRequest([
            'company_id' => self::COMPANY_ID,
        ]);

        $controller = new UploadFile();

        try {
            $controller->getPicUploadToken($request);
            $this->fail('TC-03: Expected ResourceException when user_id and account_id are both missing');
        } catch (ResourceException $e) {
            $this->assertNotSame('', trim($e->getMessage()), 'TC-03: exception message should be explicit');
        }
    }

    /**
     * TC-05 / A7：mock getToken 返回结构 → 响应含既有 token 字段。
     * #given factory mock 返回完整 token 结构
     * #when getPicUploadToken
     * #then 响应含 token、domain、region、key
     */
    public function testTc05ResponseContainsTokenFieldsFromFactory(): void
    {
        $payload = $this->sampleTokenPayload();

        $this->bindResponseFactory();
        $capturedGetKey = null;
        $capturedIncrKey = null;
        $this->bindRedisMock($capturedGetKey, $capturedIncrKey);
        $this->bindUploadTokenFactory($payload);

        $request = $this->createRequest([
            'company_id' => self::COMPANY_ID,
            'user_id' => 100,
        ], [
            'filetype' => 'image',
            'filename' => 'license.jpg',
            'group' => 'merchant',
        ]);

        $controller = new UploadFile();
        $result = $controller->getPicUploadToken($request);

        $this->assertSame($payload['token'], $result['token']);
        $this->assertSame($payload['domain'], $result['domain']);
        $this->assertSame($payload['region'], $result['region']);
        $this->assertSame($payload['key'], $result['key']);
    }

    /**
     * @return array{token: string, domain: string, region: string, key: string}
     */
    private function sampleTokenPayload(): array
    {
        return [
            'token' => 'mock-upload-token',
            'domain' => 'https://cdn.example.com',
            'region' => 'cn-shanghai',
            'key' => self::COMPANY_ID . '/merchant/license.jpg',
        ];
    }

    /**
     * @param array<string, mixed> $auth
     * @param array<string, mixed> $query
     */
    private function createRequest(array $auth, array $query = []): Request
    {
        $request = Request::create('/wxapp/espier/image_upload_token', 'GET', $query);
        $request->merge(['auth' => $auth]);

        return $request;
    }

    private function bindResponseFactory(): void
    {
        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('array')->andReturnUsing(static fn (array $payload): array => $payload);
        $this->app->instance(Factory::class, $factory);
    }

    /**
     * @param mixed $capturedGetKey
     * @param mixed $capturedIncrKey
     */
    private function bindRedisMock(&$capturedGetKey, &$capturedIncrKey, int $getReturn = 0): void
    {
        $mockConnection = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['get', 'incr', 'expire'])
            ->getMock();
        $mockConnection->method('get')->willReturnCallback(
            function (string $key) use (&$capturedGetKey, $getReturn) {
                $capturedGetKey = $key;

                return $getReturn;
            }
        );
        $mockConnection->method('incr')->willReturnCallback(
            function (string $key) use (&$capturedIncrKey): int {
                $capturedIncrKey = $key;

                return 1;
            }
        );
        $mockConnection->method('expire')->willReturn(true);

        $mockRedis = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['connection'])
            ->getMock();
        $mockRedis->method('connection')->with('members')->willReturn($mockConnection);

        $this->app->instance('redis', $mockRedis);
    }

    private function bindRedisNeverCalled(): void
    {
        $mockConnection = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['get', 'incr', 'expire'])
            ->getMock();
        $mockConnection->expects($this->never())->method('get');
        $mockConnection->expects($this->never())->method('incr');
        $mockConnection->expects($this->never())->method('expire');

        $mockRedis = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['connection'])
            ->getMock();
        $mockRedis->expects($this->never())->method('connection');

        $this->app->instance('redis', $mockRedis);
    }

    /**
     * @param array{token: string, domain: string, region: string, key: string} $payload
     */
    private function bindUploadTokenFactory(array $payload): void
    {
        $tokenService = Mockery::mock(UploadTokenInterface::class);
        $tokenService->shouldReceive('getToken')
            ->once()
            ->with(self::COMPANY_ID, Mockery::any(), Mockery::any())
            ->andReturn($payload);

        $factoryMock = Mockery::mock('overload:EspierBundle\Services\UploadTokenFactoryService');
        $factoryMock->shouldReceive('create')
            ->once()
            ->with(Mockery::any())
            ->andReturn($tokenService);
    }

    private function bindUploadTokenFactoryNeverCalled(): void
    {
        $factoryMock = Mockery::mock('overload:EspierBundle\Services\UploadTokenFactoryService');
        $factoryMock->shouldReceive('create')->never();
    }
}
