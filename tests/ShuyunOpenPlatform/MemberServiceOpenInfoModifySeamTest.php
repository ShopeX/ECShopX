<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

class MemberServiceOpenInfoModifySeamTest extends TestCase
{
    public function testMemberServiceExposesOpenMergeAndModifyHelpers(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Services/MemberService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('function isShuyunOpenPlatformMemberEnabled', $src);
        $this->assertStringContainsString('function mergeShuyunOpenPlatformEnhanceIntoMemberInfo', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformMemberModifyService::class', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformMemberInfoQueryService::class', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformMemberEnhanceDetailQueryService::class', $src);
        // 单次请求内 enhance 查询去重（避免 merge + 积分各打一次网关）
        $this->assertStringContainsString('REQUEST_ATTR_SHUYUN_ENHANCE_SNAPSHOT_CACHE', $src);
        $this->assertStringContainsString('rememberShuyunEnhanceMemberSnapshotInRequestCache', $src);
        // 数云 OPEN 会员 id / platAccount 统一为本地 members.user_id；merge 路径按 reg_distributor 取店并走 enhance.member.post；积分余额走 enhance.member.query.detail
        $this->assertStringContainsString("->querySingle(", $src);
        $this->assertStringContainsString("->queryDetail(", $src);
        $this->assertStringContainsString("\$memberInfo['reg_distributor']", $src);
        $this->assertStringContainsString("'merge skipped',", $src);
        $this->assertStringNotContainsString('querySingle($companyId, $virtual, $unionId)', $src);
        $this->assertStringContainsString(
            "\$virtual,\n                (string) \$userId,\n                \$changes",
            $src
        );
    }

    public function testWxappUpdateMemberCallsOpenModifyBeforeLocalUpdate(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Http/FrontApi/V1/Action/Members.php';
        $src = (string) file_get_contents($path);

        $openBefore = strpos($src, 'isShuyunOpenPlatformMemberEnabled($companyId)');
        $localUpdate = strpos($src, 'memberInfoUpdate($postData, $filter)');
        $this->assertNotFalse($openBefore);
        $this->assertNotFalse($localUpdate);
        $this->assertLessThan($localUpdate, $openBefore);

        $this->assertStringContainsString(
            '! $this->memberService->isShuyunOpenPlatformMemberEnabled($companyId) && config(\'common.oem-shuyun\')',
            $src
        );
    }
}
