<?php

class EmailActivationMailViewTest extends TestCase
{
    public function testZhLocaleRendersHeadlineAndBrand(): void
    {
        app()->setLocale('zh-CN');
        $html = view('members.email_activation', [
            'brand' => '我的店',
            'activationUrl' => 'https://h5.example.com/subpages/auth/email-activate?token=abc%2F&company_id=1',
        ])->render();

        $this->assertStringContainsString('验证你的邮箱', $html);
        $this->assertStringContainsString('我的店', $html);
        $this->assertStringContainsString('成为会员只差一步', $html);
        $this->assertStringContainsString('subpages/auth/email-activate', $html);
        $this->assertStringContainsString('token=abc%2F', $html);
    }

    public function testBrandHtmlEscaped(): void
    {
        app()->setLocale('zh-CN');
        $html = view('members.email_activation', [
            'brand' => '<script>alert(1)</script>',
            'activationUrl' => 'https://example.com/subpages/auth/email-activate?token=x&company_id=1',
        ])->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testEnLocaleRendersEnglishCopy(): void
    {
        app()->setLocale('en-CN');
        $html = view('members.email_activation', [
            'brand' => 'ECShopX',
            'activationUrl' => 'https://x/subpages/auth/email-activate?token=t&company_id=2',
        ])->render();

        $this->assertStringContainsString('Verify your email', $html);
        $this->assertStringContainsString('One step left to become a member', $html);
        $this->assertStringContainsString('ECShopX', $html);
    }
}
