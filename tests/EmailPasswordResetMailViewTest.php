<?php

use MembersBundle\Services\MemberPasswordResetService;

class EmailPasswordResetMailViewTest extends TestCase
{
    public function testZhLocaleRendersIntroButtonAndMinutes(): void
    {
        app()->setLocale('zh-CN');
        $html = view('members.email_password_reset', [
            'brand' => '我的店',
            'resetUrl' => 'https://h5.example.com/reset-password?token=abc%2F&company_id=1',
            'ttlMinutes' => 15,
        ])->render();

        $this->assertStringContainsString('我的店', $html);
        $this->assertStringContainsString('重置密码', $html);
        $this->assertStringContainsString('我们收到了重置您账户密码的请求。', $html);
        $this->assertStringContainsString('reset-password', $html);
        $this->assertStringContainsString('token=abc%2F', $html);
        $this->assertStringContainsString('15', $html);
        $this->assertStringContainsString('分钟后过期', $html);
        $this->assertStringContainsString('如果您没有请求重置密码', $html);
    }

    public function testBrandHtmlEscaped(): void
    {
        app()->setLocale('zh-CN');
        $html = view('members.email_password_reset', [
            'brand' => '<script>alert(1)</script>',
            'resetUrl' => 'https://example.com/reset-password?token=x&company_id=1',
            'ttlMinutes' => 5,
        ])->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testEnLocaleRendersEnglishCopy(): void
    {
        app()->setLocale('en-CN');
        $html = view('members.email_password_reset', [
            'brand' => 'ECShopX',
            'resetUrl' => 'https://x.example/reset-password?token=t&company_id=2',
            'ttlMinutes' => 20,
        ])->render();

        $this->assertStringContainsString('ECShopX', $html);
        $this->assertStringContainsString('We received a request to reset the password for your account.', $html);
        $this->assertStringContainsString('20', $html);
        $this->assertStringContainsString('will expire in', $html);
        $this->assertStringContainsString('If you did not request a password reset', $html);
    }

    public function testIsAllowedResetEmailUrlAcceptsHttpHttps(): void
    {
        $this->assertTrue(MemberPasswordResetService::isAllowedResetEmailUrl('https://h5.example.com/reset-password?token=abc&company_id=1'));
        $this->assertTrue(MemberPasswordResetService::isAllowedResetEmailUrl('http://localhost/reset-password?token=x&company_id=1'));
    }

    public function testIsAllowedResetEmailUrlRejectsDangerousOrInvalid(): void
    {
        $this->assertFalse(MemberPasswordResetService::isAllowedResetEmailUrl('javascript:alert(1)'));
        $this->assertFalse(MemberPasswordResetService::isAllowedResetEmailUrl(''));
        $this->assertFalse(MemberPasswordResetService::isAllowedResetEmailUrl('ftp://example.com/x'));
        $this->assertFalse(MemberPasswordResetService::isAllowedResetEmailUrl('/reset-password?token=x'));
    }
}
