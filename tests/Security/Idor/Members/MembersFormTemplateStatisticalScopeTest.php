<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Members;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Support\MembersOwnerScopeGuard;
use SelfserviceBundle\Http\FrontApi\V1\Action\FormTemplateController;

/**
 * codex-security-03-high-idor T25 / F-015 / TC-03-06b
 */
class MembersFormTemplateStatisticalScopeTest extends \TestCase
{
    /**
     * TC-03-06b / F-015：表单统计跨 user 须 fail-closed 拒绝。
     * #given 请求 user_id=200，auth user_id=100
     * #when assertRequestedUserMatchesAuth
     * #then ResourceException
     */
    public function testTc0306bCrossUserFormStatisticalRejected(): void
    {
        #given / #when / #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('无权访问该数据');

        MembersOwnerScopeGuard::assertRequestedUserMatchesAuth(200, 100);
    }

    /**
     * TC-03-06b / F-015：statisticalAnalysis 须绑定 auth user，拒绝外源 user_id。
     * #given FormTemplateController::statisticalAnalysis 源码
     * #when 检查 owner 绑定
     * #then 须调用 MembersOwnerScopeGuard
     */
    public function testTc0306bStatisticalAnalysisBindsAuthUser(): void
    {
        #given
        $body = $this->methodBody(FormTemplateController::class, 'statisticalAnalysis');

        #when / #then
        $this->assertStringContainsString('MembersOwnerScopeGuard', $body);
        $this->assertStringContainsString('assertRequestedUserMatchesAuth', $body);
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
