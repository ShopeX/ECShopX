<?php

declare(strict_types=1);

namespace Tests\Security\Low;

use Illuminate\Http\Request;
use OpenapiBundle\Middleware\OpenapiCheck;
use OpenapiBundle\Middleware\OpenapiCommonCheck;
use TestCase;

/**
 * codex-security-05-low T40-RED / F-185 / TC-05-05
 */
class OpenapiCommonTokenFailClosedTest extends TestCase
{
    /**
     * TC-05-05 / F-185：OPENAPI_COMMON_TOKEN 未配置时须 fail-closed 拒绝请求。
     * #given common_token 为空且 debug 关闭
     * #when 请求经 OpenapiCommonCheck
     * #then 不得放行到 next 中间件
     */
    public function testTc0505RejectsWhenCommonTokenMissing(): void
    {
        #given
        config(['openapi.debug' => 0, 'openapi.common_token' => null]);
        $payload = [
            'version' => '1.0',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $payload['sign'] = OpenapiCheck::gen_sign($payload, '');
        $middleware = new OpenapiCommonCheck();
        $request = Request::create('/openapi/common', 'POST', $payload);
        $passed = false;

        #when
        $response = $middleware->handle($request, static function () use (&$passed) {
            $passed = true;

            return response('ok', 200);
        });

        #then
        $this->assertFalse($passed, 'TC-05-05: missing OPENAPI_COMMON_TOKEN must fail-closed');
        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('fail', $payload['status'] ?? null);
    }

    /**
     * TC-05-05 / F-185：源码须显式校验 common_token 非空。
     * #given OpenapiCommonCheck::handle
     * #when 检查 token 配置校验
     * #then 须在签名比对前 fail-closed
     */
    public function testTc0505SourceExplicitlyGuardsEmptyCommonToken(): void
    {
        #given
        $body = $this->methodBody(OpenapiCommonCheck::class, 'handle');

        #when / #then
        $this->assertTrue(
            preg_match('/empty\s*\(\s*\$token\s*\)|!\s*\$token|throw\s+new\s+Exception\s*\([^)]*token/si', $body) === 1,
            'OpenapiCommonCheck must throw or reject when common_token is empty before sign compare'
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
