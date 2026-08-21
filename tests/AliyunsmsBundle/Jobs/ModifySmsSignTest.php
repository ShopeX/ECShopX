<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\AliyunsmsBundle\Jobs;

use AliyunsmsBundle\Jobs\ModifySmsSign;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

/** @see .tasks/plans/aliyun-sms-sign-sync.md TC-18 */
class ModifySmsSignTest extends \TestCase
{
    /**
     * TC-18: ModifySmsSign 应直接调 updateSmsSign 且不再依赖 getImg。
     */
    public function testTc18ModifySmsSignCallsUpdateSmsSignWithRequiredFieldsOnly(): void
    {
        // #given
        $expectedParams = [
            'company_id' => 100,
            'sign_name' => 'DbSignName',
            'sign_source' => 2,
            'remark' => 'remark text',
            'third_party' => true,
            'qualification_id' => 'qual-1',
        ];

        $client = $this->getMockBuilder(AliyunSmsClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateSmsSign'])
            ->getMock();
        $client->expects($this->once())
            ->method('updateSmsSign')
            ->with($this->callback(function (array $params): bool {
                return $params['sign_name'] === 'DbSignName'
                    && $params['sign_source'] === 2
                    && $params['remark'] === 'remark text'
                    && $params['third_party'] === true
                    && $params['qualification_id'] === 'qual-1'
                    && !array_key_exists('sign_file', $params)
                    && !array_key_exists('delegate_file', $params);
            }))
            ->willReturn(true);

        $job = $this->getMockBuilder(ModifySmsSign::class)
            ->setConstructorArgs([$expectedParams])
            ->onlyMethods(['makeClient'])
            ->getMock();
        $job->method('makeClient')->willReturn($client);

        // #when
        $result = $job->handle();

        // #then
        $this->assertTrue($result);
    }
}
