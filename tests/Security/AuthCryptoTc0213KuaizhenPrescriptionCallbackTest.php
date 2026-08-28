<?php

declare(strict_types=1);

namespace Tests\Security;

use TestCase;

/**
 * codex-security-02-high-auth-crypto T10 / F-046 / TC-02-13
 */
class AuthCryptoTc0213KuaizhenPrescriptionCallbackTest extends TestCase
{
    /**
     * TC-02-13 / AC-02-12：处方回调路由须验签。
     * #given kuaizhen 路由
     * #when 检查 prescriptionMedicationAndAudit
     * #then 挂 Kuaizhen580CallbackCheck
     */
    public function testTc0213PrescriptionCallbackRouteUsesSignatureMiddleware(): void
    {
        #given
        $routes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/thirdparty/kuaizhen.php');

        #when / #then
        $this->assertStringContainsString('Kuaizhen580CallbackCheck', $routes);
        $this->assertStringContainsString('prescriptionMedicationAndAudit', $routes);
    }

    /**
     * TC-02-13 / AC-02-12：处方回调不得对任意 URL 出站拉取 dstFilePath。
     * #given DiagnosisService 源码
     * #when 检查 dstFilePath 处理
     * #then 须经 OutboundUrlAllowlist 校验
     */
    public function testTc0213PrescriptionAuditUsesOutboundUrlAllowlist(): void
    {
        #given
        $source = (string) file_get_contents(
            dirname(__DIR__, 2).'/src/ThirdPartyBundle/Services/Kuaizhen580Center/Src/DiagnosisService.php'
        );

        #when / #then
        $this->assertStringContainsString('OutboundUrlAllowlist', $source);
        $this->assertStringContainsString('dstFilePath', $source);
    }
}
