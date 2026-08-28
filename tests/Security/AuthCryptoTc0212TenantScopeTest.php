<?php

declare(strict_types=1);

namespace Tests\Security;

use SelfserviceBundle\Http\Api\V1\Action\RegistrationRecordController;
use FormBundle\Http\Api\V1\Action\UserTranscripts;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T12 / F-065,F-067 / TC-02-12a,12b
 */
class AuthCryptoTc0212TenantScopeTest extends TestCase
{
    /**
     * TC-02-12a / AC-02-11：报名记录详情须按当前租户 company_id 过滤。
     * #given getDataInfo 源码
     * #when 检查租户过滤
     * #then 须使用 company_id
     */
    public function testTc0212aRegistrationRecordGetDataInfoScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(RegistrationRecordController::class, 'getDataInfo');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertStringContainsString("app('auth')->user()->get('company_id')", $body);
    }

    /**
     * TC-02-12b / AC-02-11：成绩单列表须将 $filter（含 company_id）传给 service，而非裸 $postdata。
     * #given getUserTranscript 源码
     * #when 检查 service 调用参数
     * #then 须传 $filter
     */
    public function testTc0212bUserTranscriptUsesFilterNotRawPostdata(): void
    {
        #given
        $body = $this->methodBody(UserTranscripts::class, 'getUserTranscript');

        #when / #then
        $this->assertStringContainsString('getUserTranscript($filter)', $body);
        $this->assertStringNotContainsString('getUserTranscript($postdata)', $body);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }
}
