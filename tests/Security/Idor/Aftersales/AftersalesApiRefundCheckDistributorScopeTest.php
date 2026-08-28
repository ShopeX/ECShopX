<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Aftersales;

use AftersalesBundle\Support\AftersalesDistributorGate;
use Dingo\Api\Exception\ResourceException;

/**
 * codex-security-03-high-idor T22：Aftersales api refundCheck 跨店（TC-03-03c）
 */
class AftersalesApiRefundCheckDistributorScopeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * TC-03-03c / F-030：api refundCheck 跨店拒绝
     * #given 售后单属于 distributor_id=3，店员 scope 为 [1, 2]
     * #when assertOwnStoreAftersales
     * #then ResourceException
     */
    public function testTc0303cApiCrossStoreRefundCheckRejected(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('无权操作该售后单');

        AftersalesDistributorGate::assertOwnStoreAftersales(
            ['distributor_id' => 3],
            [1, 2]
        );
    }
}
