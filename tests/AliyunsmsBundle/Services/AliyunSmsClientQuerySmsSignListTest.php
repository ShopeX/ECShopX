<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\AliyunsmsBundle\Services;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\QuerySmsSignListRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\QuerySmsSignListResponse;

/** @see .tasks/plans/aliyun-sms-sign-sync.md TC-16 */
class AliyunSmsClientQuerySmsSignListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 3));
        $app->withFacades();
        $app->instance('log', new class {
            public function debug($msg): void
            {
            }

            public function error($msg): void
            {
            }
        });
    }

    private function makeClient(): AliyunSmsClient
    {
        return $this->getMockBuilder(AliyunSmsClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createClient'])
            ->getMock();
    }

    /**
     * TC-16: PageSize 越界（0）应校验失败。
     */
    public function testTc16PageSizeZeroThrowsInvalidArgumentException(): void
    {
        // #given
        $client = $this->makeClient();

        // #when #then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PageSize must be between 1 and 50');
        $client->querySmsSignList(1, 0);
    }

    /**
     * TC-16: PageSize 越界（51）应校验失败。
     */
    public function testTc16PageSize51ThrowsInvalidArgumentException(): void
    {
        // #given
        $client = $this->makeClient();

        // #when #then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PageSize must be between 1 and 50');
        $client->querySmsSignList(1, 51);
    }

    /**
     * 支撑 querySmsSignList 可测：合法分页参数应传给 SDK 并返回 body。
     */
    public function testQuerySmsSignListPassesValidPaginationParamsToSdk(): void
    {
        // #given
        $sdkClient = $this->createMock(Dysmsapi::class);
        $mockResponse = $this->createMock(QuerySmsSignListResponse::class);
        $mockResponse->method('toMap')->willReturn([
            'body' => [
                'Code' => 'OK',
                'TotalCount' => 0,
                'SmsSignList' => [],
            ],
        ]);
        $sdkClient->expects($this->once())
            ->method('querySmsSignList')
            ->with($this->callback(function ($request) {
                return $request instanceof QuerySmsSignListRequest
                    && $request->pageIndex === 2
                    && $request->pageSize === 50;
            }))
            ->willReturn($mockResponse);

        $client = $this->getMockBuilder(AliyunSmsClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createClient'])
            ->getMock();
        $client->method('createClient')->willReturn($sdkClient);

        // #when
        $result = $client->querySmsSignList(2, 50);

        // #then
        $this->assertSame('OK', $result['Code']);
        $this->assertSame(0, $result['TotalCount']);
    }
}
