<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

class MemberRegisterCurrentStateSourceTest extends TestCase
{
    public function testBackofficeCreateMemberPathKeepsCurrentStateWithoutBindPushOrRegisterJob(): void
    {
        $path = dirname(__DIR__, 2).'/src/MembersBundle/Http/Api/V1/Action/Members.php';
        $src = (string) file_get_contents($path);

        $start = strpos($src, 'public function createMember(Request $request)');
        $this->assertNotFalse($start);
        $end = strpos($src, 'public function batchUpdateMemberRegDistributor', $start);
        if ($end === false) {
            $end = strlen($src);
        }
        $segment = substr($src, (int) $start, (int) $end - (int) $start);

        $this->assertStringNotContainsString('bind.push', $segment);
        $this->assertStringNotContainsString('MemberRegisterJob', $segment);
        $this->assertStringContainsString('CreateMemberSuccessEvent', $segment);
        $this->assertStringContainsString('syncUserCardCodeFromShuyunEnhanceAfterRegister', $segment);
        $this->assertStringContainsString('$distributorRow,', $segment);
        $this->assertStringContainsString('true', $segment);
    }
}

