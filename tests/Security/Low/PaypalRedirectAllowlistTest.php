<?php

declare(strict_types=1);

namespace Tests\Security\Low;

use Illuminate\Http\Request;
use PaymentBundle\Http\Controllers\PaypalNotify;
use TestCase;

/**
 * codex-security-05-low T40-RED / F-183,F-184 / TC-05-03
 */
class PaypalRedirectAllowlistTest extends TestCase
{
    /**
     * TC-05-03 / F-183：PayPal 取消回调不得无条件重定向到外域 URL。
     * #given cancel_url 指向 evil.com
     * #when 调用 PaypalNotify::cancel
     * #then 不得重定向至 evil.com
     */
    public function testTc0503PaypalCancelRejectsExternalRedirect(): void
    {
        #given
        $evilUrl = 'https://evil.com/phish';
        $request = Request::create('/payment/paypal/cancel', 'GET', ['cancel_url' => $evilUrl]);
        $controller = new PaypalNotify();

        #when
        $response = $controller->cancel($request);

        #then
        $this->assertNotSame(
            $evilUrl,
            $response->getTargetUrl(),
            'TC-05-03: PayPal cancel must not redirect to arbitrary external URLs'
        );
    }

    /**
     * TC-05-03 / F-184：PayPal 成功回调须校验 return_url/cancel_url 白名单。
     * #given PaypalNotify::handle
     * #when 检查重定向前 allowlist 逻辑
     * #then 须引用 OutboundUrlAllowlist 或等效校验
     */
    public function testTc0503PaypalHandleValidatesRedirectUrls(): void
    {
        #given
        $body = $this->methodBody(PaypalNotify::class, 'handle');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'OutboundUrlAllowlist',
                'assertAllowedRedirect',
                'assertAllowed',
                'validateRedirectUrl',
            ]),
            'PayPal handle must validate return_url/cancel_url before redirect'
        );
    }

    /**
     * @param string[] $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
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
