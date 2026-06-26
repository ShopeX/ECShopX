<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

class MemberEnhanceQueryVirtualDistributorSourceTest extends TestCase
{
    public function testEnhanceQueryUsesOnlinePlatWhenDistributorIsVirtual(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Services/MemberService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('private function shouldForceOfflinePlatForEnhanceQuery(array $distributorRow): bool', $src);
        $this->assertStringContainsString('$isVirtual = (int) ($distributorRow[\'distributor_self\'] ?? 0) === 1;', $src);
        $this->assertStringContainsString('return !$isVirtual;', $src);
        $this->assertStringContainsString('$this->shouldForceOfflinePlatForEnhanceQuery($distributorRow)', $src);
    }
}
