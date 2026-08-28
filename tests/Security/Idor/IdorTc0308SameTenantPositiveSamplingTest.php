<?php

declare(strict_types=1);

namespace Tests\Security\Idor;

use EspierBundle\Support\ExportLogTenantScopeGuard;
use GoodsBundle\Support\GoodsTenantScopeGuard;
use PaymentBundle\Services\PaymentTenantScopeGuard;
use TestCase;

/**
 * codex-security-03-high-idor T27 / AC-03-07 / TC-03-08
 */
class IdorTc0308SameTenantPositiveSamplingTest extends TestCase
{
    /**
     * TC-03-08 / AC-03-07：本租户合法 company_id 正向抽样 — 归属一致时不应拒绝。
     * #given 各 Bundle 租户守门 assertCompanyScope
     * #when 传入与 auth company 一致的 row
     * #then 不抛出 ResourceException
     */
    public function testTc0308SameTenantCompanyScopeGuardsAllowMatchingCompanyId(): void
    {
        #given
        $companyId = 1001;
        $row = ['company_id' => $companyId];

        #when / #then
        ExportLogTenantScopeGuard::assertCompanyScope($row, $companyId);
        PaymentTenantScopeGuard::assertCompanyScope($row, $companyId);
        GoodsTenantScopeGuard::assertCompanyScope($row, $companyId);

        $this->assertTrue(true);
    }
}
