<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitIssuingMemberResolver;
use TestCase;

class ShuyunOfflineBenefitIssuingMemberResolverTest extends TestCase
{
    public function testNumericModeReturnsUserIdForDigits(): void
    {
        config(['shuyun_open_platform.offline_benefit_member_resolve_mode' => 'numeric_user_id']);
        $r = new ShuyunOfflineBenefitIssuingMemberResolver();

        $this->assertSame(12345, $r->resolveLocalUserId(1, '12345'));
        $this->assertNull($r->resolveLocalUserId(1, 'abc'));
        $this->assertNull($r->resolveLocalUserId(1, ''));
    }
}
