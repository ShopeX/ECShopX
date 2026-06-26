<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShopSyncLifecycleResolver;

class ShopSyncLifecycleResolverTest extends \TestCase
{
    public function testResolveLifecycleFromIsValidLiterals(): void
    {
        $resolver = new ShopSyncLifecycleResolver();
        $this->assertSame(ShopSyncLifecycleResolver::ENABLED, $resolver->resolve(['is_valid' => 'true']));
        $this->assertSame(ShopSyncLifecycleResolver::ENABLED, $resolver->resolve(['is_valid' => '1']));
        $this->assertSame(ShopSyncLifecycleResolver::DISABLED, $resolver->resolve(['is_valid' => 'false']));
        $this->assertSame(ShopSyncLifecycleResolver::DISABLED, $resolver->resolve(['is_valid' => '0']));
        $this->assertSame(ShopSyncLifecycleResolver::CLOSED, $resolver->resolve(['is_valid' => 'closed']));
        $this->assertSame(ShopSyncLifecycleResolver::DELETED, $resolver->resolve(['is_valid' => 'delete']));
    }
}
