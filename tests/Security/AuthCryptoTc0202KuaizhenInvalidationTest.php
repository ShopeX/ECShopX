<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Http\Request;
use TestCase;
use ThirdPartyBundle\Middleware\Kuaizhen580CallbackCheck;

/**
 * codex-security-02-high-auth-crypto T10 / F-044 / TC-02-02
 */
class AuthCryptoTc0202KuaizhenInvalidationTest extends TestCase
{
    /**
     * TC-02-02 / AC-02-01：快诊作废/拒方路由须挂验签中间件。
     * #given kuaizhen 路由文件
     * #when 检查作废/拒方路由组
     * #then 须含 Kuaizhen580CallbackCheck
     */
    public function testTc0202KuaizhenInvalidationRoutesUseSignatureMiddleware(): void
    {
        #given
        $routes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/thirdparty/kuaizhen.php');

        #when / #then
        $this->assertStringContainsString('Kuaizhen580CallbackCheck', $routes);
        $this->assertStringContainsString('prescriptionMedicationDelete', $routes);
        $this->assertStringContainsString('refusePrescribe', $routes);
    }

    /**
     * TC-02-02 / AC-02-01：无签名的快诊作废回调须拒绝。
     * #given 无 sign 的 POST 请求
     * #when 经 Kuaizhen580CallbackCheck
     * #then 返回 403
     */
    public function testTc0202PrescriptionDeleteWithoutSignIsRejected(): void
    {
        #given
        $middleware = new Kuaizhen580CallbackCheck();
        $request = Request::create(
            '/third/kuaizhen/prescriptionMedicationDelete',
            'POST',
            ['bizOrderId' => 'ORDER-1', 'clientId' => 'cid-1', 'timeStamp' => '1690000000000']
        );

        #when
        $response = $middleware->handle($request, static fn () => response('ok', 200));

        #then
        $this->assertSame(403, $response->getStatusCode());
    }
}
