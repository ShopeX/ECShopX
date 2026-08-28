<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Selfservice;

use SelfserviceBundle\Http\Api\V1\Action\FormSettingController;
use SelfserviceBundle\Http\Api\V1\Action\FormTemplateController;
use SelfserviceBundle\Http\Api\V1\Action\RegistrationActivityController;
use SelfserviceBundle\Http\Api\V1\Action\RegistrationRecordController;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN / F-108..111,F-118..121 / AC-04-01
 */
class SelfserviceTenantScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-108,F-109：表单元素废弃/还原须校验租户归属。
     * #given FormSettingController deleteData/restoreData
     * #when 检查租户 scope
     * #then 须调用 SelfserviceTenantScopeGuard
     */
    public function testTc0401FormSettingDiscardRestoreScopeByAuthCompanyId(): void
    {
        #given
        $deleteBody = $this->methodBody(FormSettingController::class, 'deleteData');
        $restoreBody = $this->methodBody(FormSettingController::class, 'restoreData');

        #when / #then
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $deleteBody);
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $restoreBody);
    }

    /**
     * TC-04-01 / F-110,F-111：表单模板废弃/还原须校验租户归属。
     * #given FormTemplateController deleteData/restoreData
     * #when 检查租户 scope
     * #then 须调用 SelfserviceTenantScopeGuard
     */
    public function testTc0401FormTemplateDiscardRestoreScopeByAuthCompanyId(): void
    {
        #given
        $deleteBody = $this->methodBody(FormTemplateController::class, 'deleteData');
        $restoreBody = $this->methodBody(FormTemplateController::class, 'restoreData');

        #when / #then
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $deleteBody);
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $restoreBody);
    }

    /**
     * TC-04-01 / F-118,F-119：报名活动删除/作废须校验租户归属。
     * #given RegistrationActivityController deleteData/restoreData
     * #when 检查租户 scope
     * #then 须调用 SelfserviceTenantScopeGuard
     */
    public function testTc0401RegistrationActivityMutationsScopeByAuthCompanyId(): void
    {
        #given
        $deleteBody = $this->methodBody(RegistrationActivityController::class, 'deleteData');
        $restoreBody = $this->methodBody(RegistrationActivityController::class, 'restoreData');

        #when / #then
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $deleteBody);
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $restoreBody);
    }

    /**
     * TC-04-01 / F-120,F-121：报名审批/核销须校验记录归属当前租户。
     * #given RegistrationRecordController registrationReview/registrationVerify
     * #when 检查租户 scope
     * #then 须调用 SelfserviceTenantScopeGuard
     */
    public function testTc0401RegistrationRecordReviewVerifyScopeByAuthCompanyId(): void
    {
        #given
        $reviewBody = $this->methodBody(RegistrationRecordController::class, 'registrationReview');
        $verifyBody = $this->methodBody(RegistrationRecordController::class, 'registrationVerify');

        #when / #then
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $reviewBody);
        $this->assertStringContainsString('SelfserviceTenantScopeGuard', $verifyBody);
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
