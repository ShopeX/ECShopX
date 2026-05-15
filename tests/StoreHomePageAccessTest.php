<?php

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Services\StoreHomePageAccess;
use PHPUnit\Framework\TestCase;

final class StoreHomePageAccessTest extends TestCase
{
    public function testDealerMismatchThrows(): void
    {
        $this->expectException(ResourceException::class);
        StoreHomePageAccess::assertRowMatchesDealer(['distributor_id' => 10], 99);
    }

    public function testDealerMatchOk(): void
    {
        $this->expectNotToPerformAssertions();
        StoreHomePageAccess::assertRowMatchesDealer(['distributor_id' => 10], 10);
    }

    public function testZeroAuthDistributorSkipsDealerCheck(): void
    {
        $this->expectNotToPerformAssertions();
        StoreHomePageAccess::assertRowMatchesDealer(['distributor_id' => 10], 0);
    }
}
