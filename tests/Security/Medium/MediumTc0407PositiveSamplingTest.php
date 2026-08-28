<?php

declare(strict_types=1);

namespace Tests\Security\Medium;

use EspierBundle\Support\UploadFileTenantScopeGuard;
use MembersBundle\Support\MembersOwnerScopeGuard;
use OrdersBundle\Support\GuideOrderTenantScopeGuard;
use SelfserviceBundle\Support\SelfserviceTenantScopeGuard;
use TestCase;
use WsugcBundle\Support\WsugcTenantScopeGuard;

/**
 * codex-security-04-medium T36 / AC-04-07 / TC-04-07
 */
class MediumTc0407PositiveSamplingTest extends TestCase
{
    /**
     * TC-04-07 / AC-04-07：中危各类别合法抽样 — 归属一致时租户/用户守门不拒绝。
     * #given 各 Bundle scope guard 与合法 company/user 输入
     * #when 传入与 auth 一致的 row 或 company_id
     * #then 不抛出 ResourceException
     */
    public function testTc0407SameTenantScopeGuardsAllowMatchingIdentity(): void
    {
        #given
        $companyId = 1001;
        $userId = 2002;
        $row = ['company_id' => $companyId];

        #when / #then — IDOR / 敏感泄露抽样
        SelfserviceTenantScopeGuard::assertCompanyScope($row, $companyId);
        WsugcTenantScopeGuard::assertCompanyScope($row, $companyId);
        UploadFileTenantScopeGuard::assertCompanyScope($row, $companyId);
        MembersOwnerScopeGuard::assertRequestedUserMatchesAuth($userId, $userId);
        GuideOrderTenantScopeGuard::assertMemberBelongsToCompany($row, $companyId);

        $this->assertTrue(true);
    }
}
