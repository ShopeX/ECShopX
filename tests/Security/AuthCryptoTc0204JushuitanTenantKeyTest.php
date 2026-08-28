<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Http\Request;
use SystemLinkBundle\Middleware\JushuitanCheck;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T10 / F-038 / TC-02-04
 */
class AuthCryptoTc0204JushuitanTenantKeyTest extends TestCase
{
    /**
     * TC-02-04 / AC-02-03：聚水潭回调不得使用硬编码 partnerkey。
     * #given JushuitanCheck 源码
     * #when 检查 partnerkey 来源
     * #then 不得硬编码 'erp'
     */
    public function testTc0204JushuitanCheckDoesNotUseHardcodedPartnerKey(): void
    {
        #given
        $source = (string) file_get_contents(
            dirname(__DIR__, 2).'/src/SystemLinkBundle/Middleware/JushuitanCheck.php'
        );

        #when / #then
        $this->assertStringNotContainsString("\$partnerkey = 'erp'", $source);
        $this->assertStringContainsString('jushuitan.app_secret', $source);
    }

    /**
     * TC-02-04 / AC-02-03：错误密钥验签失败。
     * #given 合法参数与错误 partnerkey
     * #when 生成 sign 对比
     * #then 不匹配
     */
    public function testTc0204WrongPartnerKeyFailsVerification(): void
    {
        #given
        config(['jushuitan.app_secret' => 'tenant-secret']);
        $params = [
            'method' => 'logistics.upload',
            'partnerid' => 'p1',
            'timestamp' => '1690000000',
        ];
        $wrongSign = JushuitanCheck::gen_sign($params, 'erp');
        $expected = JushuitanCheck::gen_sign($params, 'tenant-secret');

        #when / #then
        $this->assertNotSame($expected, $wrongSign);

        $middleware = new JushuitanCheck();
        $request = Request::create(
            '/systemlink/jushuitan/100?'.http_build_query(array_merge($params, ['sign' => $wrongSign])),
            'POST'
        );

        $response = $middleware->handle($request, static fn () => response()->json(['code' => 1]));
        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(0, $payload['code'] ?? null);
        $this->assertSame('sign error', $payload['msg'] ?? null);
    }
}
