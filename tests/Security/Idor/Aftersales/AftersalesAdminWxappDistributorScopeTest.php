<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Aftersales;

use AftersalesBundle\Support\AftersalesDistributorGate;
use Dingo\Api\Exception\ResourceException;

/**
 * codex-security-03-high-idor T22：Aftersales adminwxapp 跨店写操作（TC-03-03b, TC-03-03d）
 */
class AftersalesAdminWxappDistributorScopeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * TC-03-03b / F-008：adminwxapp 跨店审核拒绝
     * #given 售后单属于 distributor_id=2，auth 本店 distributor_id=1
     * #when assertOwnStoreAftersales
     * #then ResourceException
     */
    public function testTc0303bAdminWxappCrossStoreReviewRejected(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('无权操作该售后单');

        AftersalesDistributorGate::assertOwnStoreAftersales(
            ['distributor_id' => 2],
            1
        );
    }

    /**
     * TC-03-03d / F-009：adminwxapp refundCheck 跨店拒绝
     * #given 售后单属于 distributor_id=99，auth 本店 distributor_id=5
     * #when assertOwnStoreAftersales
     * #then ResourceException
     */
    public function testTc0303dAdminWxappCrossStoreRefundCheckRejected(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('无权操作该售后单');

        AftersalesDistributorGate::assertOwnStoreAftersales(
            ['distributor_id' => 99],
            5
        );
    }
}
