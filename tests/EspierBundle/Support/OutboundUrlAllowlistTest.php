<?php

declare(strict_types=1);

namespace Tests\EspierBundle\Support;

use EspierBundle\Support\OutboundUrlAllowlist;

/**
 * codex-security-01-platform-baseline T04：OutboundUrlAllowlist（TC-01-08）
 */
class OutboundUrlAllowlistTest extends \TestCase
{
    /**
     * TC-01-08 / AC-01-07：拒 127.0.0.1/10.x/file://
     * #given 私网/环回/危险 scheme 的出站 URL
     * #when 调用 isAllowed
     * #then 返回 false
     */
    public function testTc0108RejectsLoopback127001(): void
    {
        #given
        $url = 'http://127.0.0.1/api';

        #when
        $allowed = OutboundUrlAllowlist::isAllowed($url);

        #then
        $this->assertFalse($allowed, 'TC-01-08: loopback 127.0.0.1 must be rejected');
    }

    /**
     * TC-01-08 / AC-01-07：拒 10.x 私网
     * #given 10.0.0.0/8 私网 URL
     * #when 调用 isAllowed
     * #then 返回 false
     */
    public function testTc0108RejectsPrivateNetwork10x(): void
    {
        #given
        $url = 'http://10.0.0.1/internal';

        #when
        $allowed = OutboundUrlAllowlist::isAllowed($url);

        #then
        $this->assertFalse($allowed, 'TC-01-08: 10.x private network must be rejected');
    }

    /**
     * TC-01-08 / AC-01-07：拒 file:// 危险 scheme
     * #given file:// URL
     * #when 调用 isAllowed
     * #then 返回 false
     */
    public function testTc0108RejectsFileScheme(): void
    {
        #given
        $url = 'file:///etc/passwd';

        #when
        $allowed = OutboundUrlAllowlist::isAllowed($url);

        #then
        $this->assertFalse($allowed, 'TC-01-08: file:// scheme must be rejected');
    }

    /**
     * TC-01-08 / AC-01-07：允许公网 https URL
     * #given 合法公网 https URL
     * #when 调用 isAllowed
     * #then 返回 true
     */
    public function testTc0108AllowsPublicHttpsUrl(): void
    {
        #given
        $url = 'https://api.example.com/v1/resource';

        #when
        $allowed = OutboundUrlAllowlist::isAllowed($url);

        #then
        $this->assertTrue($allowed, 'TC-01-08: public https URL must be allowed');
    }

    /**
     * TC-01-08 / AC-01-07：assertAllowed 对非法 URL 抛异常
     * #given 环回 URL
     * #when 调用 assertAllowed
     * #then 抛出 InvalidArgumentException
     */
    public function testTc0108AssertAllowedThrowsForBlockedUrl(): void
    {
        #given
        $url = 'http://127.0.0.1/admin';

        #when / #then
        $this->expectException(\InvalidArgumentException::class);
        OutboundUrlAllowlist::assertAllowed($url);
    }
}
