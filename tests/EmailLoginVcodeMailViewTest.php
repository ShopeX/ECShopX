<?php

class EmailLoginVcodeMailViewTest extends TestCase
{
    public function testZhLocaleRendersIntroCodeAndMinutes(): void
    {
        app()->setLocale('zh-CN');
        $html = view('members.email_login_vcode', [
            'brand' => '我的店',
            'code' => '123456',
            'ttlMinutes' => 10,
        ])->render();

        $this->assertStringContainsString('我的店', $html);
        $this->assertStringContainsString('登录到我的店', $html);
        $this->assertStringContainsString('您的一次性验证码是：', $html);
        $this->assertStringContainsString('123456', $html);
        $this->assertStringContainsString('10', $html);
        $this->assertStringContainsString('分钟后过期', $html);
        $this->assertStringContainsString('如果您没有请求登录', $html);
    }

    public function testBrandHtmlEscaped(): void
    {
        app()->setLocale('zh-CN');
        $html = view('members.email_login_vcode', [
            'brand' => '<script>alert(1)</script>',
            'code' => '654321',
            'ttlMinutes' => 5,
        ])->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testEnLocaleRendersEnglishCopy(): void
    {
        app()->setLocale('en-CN');
        $html = view('members.email_login_vcode', [
            'brand' => 'ECShopX',
            'code' => '111222',
            'ttlMinutes' => 10,
        ])->render();

        $this->assertStringContainsString('Sign in to ECShopX', $html);
        $this->assertStringContainsString('Your one-time verification code is:', $html);
        $this->assertStringContainsString('111222', $html);
        $this->assertStringContainsString('10 minutes', $html);
    }

    public function testShortTtlShowsCeiledMinutes(): void
    {
        app()->setLocale('zh-CN');
        $html = view('members.email_login_vcode', [
            'brand' => 'ECShopX',
            'code' => '000000',
            'ttlMinutes' => 2,
        ])->render();

        $this->assertStringContainsString('2', $html);
        $this->assertStringContainsString('分钟后过期', $html);
    }
}
