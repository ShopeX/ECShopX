<?php

/**
 * 专用重发激活邮件：配置与文案（路由集成依赖环境，此处做配置与翻译断言）。
 */
class MemberResendActivationEmailTest extends TestCase
{
    public function testMemberEmailActivationCooldownDefaultIs90Seconds(): void
    {
        $this->assertSame(90, (int) config('common.member_email_activation_cooldown_seconds'));
    }

    public function testResendTooFrequentTranslationZh(): void
    {
        app()->setLocale('zh-CN');
        $this->assertSame(
            '请勿频繁请求，稍后再做尝试',
            trans('MembersBundle/Members.email_activation_resend_too_frequent')
        );
    }

    public function testResendTooFrequentTranslationEn(): void
    {
        app()->setLocale('en-CN');
        $this->assertStringContainsString(
            'Try again later',
            trans('MembersBundle/Members.email_activation_resend_too_frequent')
        );
    }
}
