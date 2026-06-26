<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

class MemberDeleteOpenUnbindSourceTest extends TestCase
{
    public function testDeleteMembersUsesOpenUnbindWithoutLpeeFallbackWhenOpenEnabled(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Services/MemberService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('CompanyShuyunOpenPlatformConfigRepository', $src);
        $this->assertStringContainsString('$openEnabled = $openConfig !== null && (int) $openConfig->getIsEnabled() === 1;', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformMemberUnbindService::class', $src);
        $this->assertStringContainsString('getShuyunOpenMemberUnbindServiceForDeleteMembers', $src);
        $this->assertStringContainsString('resolveOfflineDistributorRowForShuyunOpenDeleteMembers', $src);
        $this->assertStringContainsString('resolveDistributorRowForOpenUnbindOnDelete', $src);
        $this->assertStringNotContainsString('shouldPerformShuyunOpenDoubleUnbindForDeleteMembers', $src);
        $this->assertStringContainsString('m.offline_reg_distributor', $src);
        $this->assertStringContainsString('} elseif (! $openEnabled && config(\'common.oem-shuyun\')) {', $src);
    }

    public function testDeleteMembersAllowsRequestDistributorAndOfflineForCompensation(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Services/MemberService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('?int $sourceDistributorIdForOpenUnbind = null', $src);
        $this->assertStringContainsString('bool $forceOfflinePlatForOpenUnbind = false', $src);
        $this->assertStringContainsString('bool $skipShuyunOpenPlatformUnbind = false', $src);
        $this->assertStringContainsString('$requestedDistributorId = (int) ($sourceDistributorIdForOpenUnbind ?? 0);', $src);
        $this->assertStringContainsString('\'distributor_id\' => $requestedDistributorId,', $src);
        $this->assertStringContainsString('$forceOfflinePlatForOpenUnbind', $src);
        $this->assertStringContainsString('if ($openEnabled && ! $skipShuyunOpenPlatformUnbind) {', $src);
        $this->assertStringContainsString('} elseif (! $openEnabled && config(\'common.oem-shuyun\')) {', $src);
    }

    public function testOfflineCreateMemberCompensationDeleteMembersSkipsUnbindWhenRegisterNotSucceeded(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Http/Api/V1/Action/Members.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('$shuyunOpenMemberRegisterSucceeded = false;', $src);
        $this->assertStringContainsString('! $shuyunOpenMemberRegisterSucceeded', $src);
        $this->assertStringContainsString(
            '(new MemberService())->deleteMembers(
                    $companyId,
                    $userId,
                    $mobile,
                    $distributorId,
                    true,
                    ! $shuyunOpenMemberRegisterSucceeded
                )',
            $src
        );
    }

    public function testStoreCreateMemberPersistsOfflineRegDistributorAfterOpenOfflineRegister(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Http/Api/V1/Action/Members.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('offline_reg_distributor', $src);
        $this->assertStringContainsString('updateMemberInfo(', $src);
    }
}

