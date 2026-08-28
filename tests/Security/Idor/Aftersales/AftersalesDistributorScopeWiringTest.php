<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Aftersales;

use AftersalesBundle\Http\AdminApi\V1\Action\Aftersales as AdminAftersales;
use AftersalesBundle\Http\Api\V1\Action\Aftersales as ApiAftersales;
use AftersalesBundle\Services\AftersalesService;

/**
 * codex-security-03-high-idor T22：Aftersales 读守门 + 双入口接线（TC-03-03r + write wiring）
 */
class AftersalesDistributorScopeWiringTest extends \TestCase
{
    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }

    /**
     * TC-03-03r：api 售后详情须校验本店 distributor scope
     * #given Api Aftersales@getAftersalesDetail
     * #when 检查方法体
     * #then 调用 AftersalesDistributorGate
     */
    public function testTc0303rApiDetailEnforcesDistributorScope(): void
    {
        $body = $this->methodBody(ApiAftersales::class, 'getAftersalesDetail');

        $this->assertStringContainsString('AftersalesDistributorGate', $body);
        $this->assertStringContainsString('assertOwnStoreAftersales', $body);
    }

    /**
     * TC-03-03r：adminwxapp 售后详情须校验本店 distributor
     * #given Admin Aftersales@getAftersalesDetail
     * #when 检查方法体
     * #then 调用 AftersalesDistributorGate
     */
    public function testTc0303rAdminWxappDetailEnforcesDistributorScope(): void
    {
        $body = $this->methodBody(AdminAftersales::class, 'getAftersalesDetail');

        $this->assertStringContainsString('AftersalesDistributorGate', $body);
        $this->assertStringContainsString('assertOwnStoreAftersales', $body);
    }

    /**
     * TC-03-03 / F-029：api review 须传递 distributor scope 并在 service 守门
     * #given Api Aftersales@aftersalesReview 与 AftersalesService::review
     * #when 检查方法体
     * #then 双入口均含 AftersalesDistributorGate
     */
    public function testTc0303ApiReviewWiresDistributorGate(): void
    {
        $apiBody = $this->methodBody(ApiAftersales::class, 'aftersalesReview');
        $serviceBody = $this->methodBody(AftersalesService::class, 'review');

        $this->assertStringContainsString('attachApiAuthScope', $apiBody);
        $this->assertStringContainsString('AftersalesDistributorGate', $serviceBody);
    }

    /**
     * TC-03-03b / F-008：adminwxapp review 须传递本店 distributor_id
     * #given Admin Aftersales@aftersalesReview
     * #when 检查方法体
     * #then 含 distributor_id 与 service 守门
     */
    public function testTc0303bAdminWxappReviewWiresDistributorGate(): void
    {
        $body = $this->methodBody(AdminAftersales::class, 'aftersalesReview');
        $serviceBody = $this->methodBody(AftersalesService::class, 'review');

        $this->assertStringContainsString('attachAdminAuthScope', $body);
        $this->assertStringContainsString('AftersalesDistributorGate', $serviceBody);
    }

    /**
     * TC-03-03c / F-030：api refundCheck 须传递 distributor scope
     * #given Api Aftersales@refundCheck
     * #when 检查方法体
     * #then 含 distributor_ids
     */
    public function testTc0303cApiRefundCheckWiresDistributorScope(): void
    {
        $body = $this->methodBody(ApiAftersales::class, 'refundCheck');

        $this->assertStringContainsString('attachApiAuthScope', $body);
        $this->assertStringContainsString('AftersalesDistributorGate', $this->methodBody(AftersalesService::class, 'confirmRefund'));
    }

    /**
     * TC-03-03d / F-009：adminwxapp refundCheck 须传递本店 distributor_id
     * #given Admin Aftersales@refundCheck
     * #when 检查方法体
     * #then 含 distributor_id
     */
    public function testTc0303dAdminWxappRefundCheckWiresDistributorScope(): void
    {
        $body = $this->methodBody(AdminAftersales::class, 'refundCheck');

        $this->assertStringContainsString('attachAdminAuthScope', $body);
        $this->assertStringContainsString('AftersalesDistributorGate', $this->methodBody(AftersalesService::class, 'confirmRefund'));
    }
}
