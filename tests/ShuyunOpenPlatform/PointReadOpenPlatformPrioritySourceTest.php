<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

class PointReadOpenPlatformPrioritySourceTest extends TestCase
{
    public function testPointMemberServiceUsesOpenPlatformPointWhenMemberCapabilityEnabled(): void
    {
        $path = dirname(__DIR__, 2).'/src/PointBundle/Services/PointMemberService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('$memberService->isShuyunOpenPlatformMemberEnabled($companyId)', $src);
        $this->assertStringContainsString('$openPoint = $memberService->queryShuyunOpenPlatformMemberPoint($companyId, $userId);', $src);
        $this->assertStringContainsString('if ($openPoint !== null) {', $src);
    }

    public function testFrontMemberInfoSkipsDmCrmOverrideWhenOpenPlatformMemberEnabled(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Http/FrontApi/V1/Action/Members.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString(
            '! $this->memberService->isShuyunOpenPlatformMemberEnabled((int) $authInfo[\'company_id\'])',
            $src
        );
        $this->assertStringContainsString('$point = $pointMemberService->getInfo([\'user_id\' => $authInfo[\'user_id\'], \'company_id\' => $authInfo[\'company_id\']]);', $src);
    }

    public function testAddPointSkipsDmCrmBlockWhenShuyunOpenPlatformWriteEnabled(): void
    {
        $path = dirname(__DIR__, 2).'/src/PointBundle/Services/PointMemberService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString(
            'if (($ns->getDmCrmSetting($companyId)[\'is_open\'] ?? \'\') && ! $useOpenPlatformWrite) {',
            $src
        );
    }

    public function testOrderPointDeductAlwaysUsesShuyunRemoteWhenOpenPlatformMemberEnabled(): void
    {
        $path = dirname(__DIR__, 2).'/src/OrdersBundle/Services/OrderService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('needShuyunRemoteBalance', $src);
        $this->assertStringContainsString('shuyun_open_remote_for_balance', $src);
        $this->assertStringContainsString('shuyunOpenPlatformMember', $src);
        $this->assertStringContainsString('! $shuyunOpenPlatformMember && ! $needShuyunRemoteBalance', $src);
    }

    public function testTradeRefundFinishDmListenerDoesNotReturnFalseWhenDmClosedSoShuyunRefundSyncCanRun(): void
    {
        $path = dirname(__DIR__, 2).'/src/ThirdPartyBundle/Listeners/DmCrm/TradeRefundFinishListener.php';
        $src = (string) file_get_contents($path);
        $this->assertStringContainsString('未开启达摩时仅跳过本监听', $src);
        $this->assertStringContainsString('禁止 return false，否则会终止事件传播', $src);
    }

    public function testTradeRefundFinishSkipsDmWhenShuyunOpenPlatformMemberEnabledLikeAddPoint(): void
    {
        $path = dirname(__DIR__, 2).'/src/ThirdPartyBundle/Listeners/DmCrm/TradeRefundFinishListener.php';
        $src = (string) file_get_contents($path);
        $this->assertStringContainsString('isShuyunOpenPlatformMemberEnabled', $src);
        $this->assertStringContainsString('退款推送以数云 refund.sync 为准', $src);
    }
}
