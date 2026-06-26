<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

final class OperatorStoreMemberReadinessServiceSourceTest extends TestCase
{
    public function testSkipsRegisterWhenEitherOpenPlatformMarkerPresent(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Services/OperatorStoreMemberReadinessService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('ShuyunOpenPlatformMemberSyncState::isRegisteredWithOpenPlatform', $src);
        $this->assertStringContainsString("return ['synced' => false, 'skipped' => true];", $src);
    }
}
