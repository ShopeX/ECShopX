<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Http\AdminApi\V1\Action\UserData;
use MembersBundle\Http\FrontApi\V1\Action\Members;
use MembersBundle\Support\MembersOwnerScopeGuard;
use OrdersBundle\Support\GuideOrderTenantScopeGuard;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN / F-076,F-090,F-091 / TC-04-01
 */
class MembersAddressOwnerScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-076：导购浏览历史须校验会员归属。
     * #given UserData@getBrowseHistory
     * #when 检查 guide-member scope
     * #then 须调用 GuideOrderTenantScopeGuard
     */
    public function testTc0401BrowseHistoryScopesGuideMemberAccess(): void
    {
        #given
        $body = $this->methodBody(UserData::class, 'getBrowseHistory');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $body);
        $this->assertStringContainsString('assertGuideCanAccessMember', $body);
    }

    /**
     * TC-04-01 / F-090,F-091：地址列表/创建须绑定 auth user。
     * #given Members getAddressList/createAddress
     * #when 检查 owner scope
     * #then 须调用 MembersOwnerScopeGuard
     */
    public function testTc0401AddressListAndCreateBindAuthUser(): void
    {
        #given
        $listBody = $this->methodBody(Members::class, 'getAddressList');
        $createBody = $this->methodBody(Members::class, 'createAddress');

        #when / #then
        $this->assertStringContainsString('MembersOwnerScopeGuard', $listBody);
        $this->assertStringContainsString('assertActingUserBoundToAuth', $listBody);
        $this->assertStringContainsString('MembersOwnerScopeGuard', $createBody);
        $this->assertStringContainsString('assertActingUserBoundToAuth', $createBody);
    }

    /**
     * TC-04-01：跨 user 代客参数须 fail-closed 拒绝。
     * #given auth user_id=100，resolved user_id=200，无合法 buy_user_id
     * #when assertActingUserBoundToAuth
     * #then ResourceException
     */
    public function testTc0401CrossUserActingUserRejected(): void
    {
        #given / #when / #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('无权访问该数据');

        MembersOwnerScopeGuard::assertActingUserBoundToAuth(
            ['user_id' => 100, 'company_id' => 1],
            ['user_id' => 200],
            200
        );
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
