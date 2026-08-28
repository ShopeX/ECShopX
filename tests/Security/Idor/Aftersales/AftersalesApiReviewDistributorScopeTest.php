<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Aftersales;

use AftersalesBundle\Support\AftersalesDistributorGate;
use Dingo\Api\Exception\ResourceException;

/**
 * codex-security-03-high-idor T22：Aftersales api 跨店写操作（TC-03-03, TC-03-03c）
 */
class AftersalesApiReviewDistributorScopeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * TC-03-03 / F-029：api 售后跨店审核拒绝
     * #given 售后单属于 distributor_id=2，店员 scope 为 [1]
     * #when assertOwnStoreAftersales
     * #then ResourceException 无权操作该售后单
     */
    public function testTc0303ApiCrossStoreReviewRejected(): void
    {
        #given / #when / #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('无权操作该售后单');

        AftersalesDistributorGate::assertOwnStoreAftersales(
            ['distributor_id' => 2],
            [1]
        );
    }
}
