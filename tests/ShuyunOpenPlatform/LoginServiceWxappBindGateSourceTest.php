<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

class LoginServiceWxappBindGateSourceTest extends TestCase
{
    public function testWxappRegisterPathSyncsOpenPlatformAfterLocalRegisterUsingUserId(): void
    {
        $path = dirname(__DIR__, 2).'/src/EspierBundle/Services/LoginService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('syncShuyunOpenPlatformWxappAfterLocalRegisterIfEnabled', $src);
        $this->assertStringNotContainsString('guardWxappBindPushBeforeLocalRegister', $src);

        $this->assertSame(
            2,
            substr_count(
                $src,
                '$this->syncShuyunOpenPlatformWxappAfterLocalRegisterIfEnabled($params, $member, $mobile, $unionId, $openId,'
            )
        );

        $this->assertStringContainsString('performShuyunOpenPlatformWxappOnlineSync', $src);
        $this->assertStringContainsString('resolveWxappOpenDistributorRowByRegDistributorId', $src);
        $this->assertStringContainsString('maybeSyncShuyunOpenPlatformWxappOnlineAfterPreLoginIfNeeded', $src);

        $this->assertStringContainsString(
            'registerSingle(
                $companyId,
                $distributorRow,
                $userIdStr,
                $mobile,
                $unionId
            )',
            $src
        );
        $this->assertStringContainsString(
            'syncUserCardCodeFromShuyunEnhanceAfterRegister(
                $companyId,
                $userId,
                $distributorRow
            )',
            $src
        );
        $this->assertStringContainsString(
            'pushSingle(
                $companyId,
                $distributorRow,
                $userIdStr,
                $unionId,
                $openId
            )',
            $src
        );
        $this->assertStringNotContainsString('bind_push_partner', $src);
        $this->assertStringContainsString('! $shuyunOpenMemberRegisterSucceeded', $src);
        $this->assertStringContainsString(
            '(new MemberService())->deleteMembers(
                        $companyId,
                        $userId,
                        $mobile,
                        null,
                        false,
                        ! $shuyunOpenMemberRegisterSucceeded
                    )',
            $src
        );
        $this->assertStringContainsString('shuyun_open_online_wxapp_sync_at', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformMemberSyncState', $src);
        $this->assertStringContainsString('performShuyunOpenPlatformWxappBindPushOnlySync', $src);
        $this->assertStringContainsString('needsWxappBindPushOnly', $src);
    }
}
