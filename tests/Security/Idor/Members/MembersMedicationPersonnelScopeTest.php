<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Members;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MedicationPersonnelService;
use MembersBundle\Support\MembersOwnerScopeGuard;

/**
 * codex-security-03-high-idor T25 / F-014 / TC-03-06
 */
class MembersMedicationPersonnelScopeTest extends \TestCase
{
    /**
     * TC-03-06 / F-014：用药人更新跨 user 须 fail-closed 拒绝。
     * #given 用药人属于 user_id=200，auth user_id=100
     * #when assertOwnUserRecord
     * #then ResourceException
     */
    public function testTc0306CrossUserMedicationPersonnelUpdateRejected(): void
    {
        #given / #when / #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('无权操作该用药人');

        MembersOwnerScopeGuard::assertOwnUserRecord(
            ['user_id' => 200, 'company_id' => 1],
            100
        );
    }

    /**
     * TC-03-06 / F-014：用药人 update 须校验 auth user 归属。
     * #given MedicationPersonnelService::update 源码
     * #when 检查 owner 绑定
     * #then 须调用 MembersOwnerScopeGuard
     */
    public function testTc0306MedicationPersonnelUpdateBindsAuthUser(): void
    {
        #given
        $body = $this->methodBody(MedicationPersonnelService::class, 'update');

        #when / #then
        $this->assertStringContainsString('MembersOwnerScopeGuard', $body);
        $this->assertStringContainsString('assertOwnUserRecord', $body);
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
