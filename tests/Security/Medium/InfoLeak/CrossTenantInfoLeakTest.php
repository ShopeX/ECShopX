<?php

declare(strict_types=1);

namespace Tests\Security\Medium\InfoLeak;

use GoodsBundle\Services\ItemsCommissionService;
use SelfserviceBundle\Http\Api\V1\Action\RegistrationActivityController;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-157,F-158 / TC-04-09
 */
class CrossTenantInfoLeakTest extends TestCase
{
    /**
     * TC-04-09 / F-157：报名活动详情须按当前租户过滤。
     * #given RegistrationActivityController@getDataInfo
     * #when 检查 activity 查询
     * #then 须将 company_id 传入仓储查询
     */
    public function testTc0409RegistrationActivityDetailScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(RegistrationActivityController::class, 'getDataInfo');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertStringContainsString('getInfoById($id,$companyId)', str_replace(' ', '', $body));
    }

    /**
     * TC-04-09 / F-158：佣金详情须校验商品归属当前租户后再返回成本价。
     * #given ItemsCommissionService::getItemsCommission
     * #when 检查 item 归属
     * #then 须调用 GoodsTenantScopeGuard
     */
    public function testTc0409CommissionDetailGuardsItemCompanyScope(): void
    {
        #given
        $body = $this->methodBody(ItemsCommissionService::class, 'getItemsCommission');

        #when / #then
        $this->assertStringContainsString('GoodsTenantScopeGuard', $body);
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
