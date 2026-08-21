<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\AliyunsmsBundle\Services;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\UpdateSmsSignRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\UpdateSmsSignResponse;
use PHPUnit\Framework\TestCase;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

/** @see .tasks/plans/aliyun-sms-sign-sync.md TC-15 */
class AliyunSmsClientUpdateSmsSignTest extends TestCase
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

    /**
     * TC-15: third_party=true 进 Client 时 SDK 应收到 true。
     */
    public function testTc15ThirdPartyTrueBoolPassedToSdkAsTrue(): void
    {
        // #given
        $sdkClient = $this->createMock(Dysmsapi::class);
        $mockResponse = $this->createMock(UpdateSmsSignResponse::class);
        $mockResponse->method('toMap')->willReturn([
            'body' => ['Code' => 'OK'],
        ]);
        $sdkClient->expects($this->once())
            ->method('updateSmsSign')
            ->with($this->callback(function ($request) {
                return $request instanceof UpdateSmsSignRequest
                    && $request->thirdParty === true;
            }))
            ->willReturn($mockResponse);

        $client = $this->getMockBuilder(AliyunSmsClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createClient'])
            ->getMock();
        $client->method('createClient')->willReturn($sdkClient);

        // #when
        $client->updateSmsSign([
            'sign_name' => 'TestSign',
            'sign_source' => 1,
            'remark' => 'remark',
            'third_party' => true,
            'qualification_id' => 'q1',
        ]);

        // #then expectation verified by mock callback
        $this->assertTrue(true);
    }
}
