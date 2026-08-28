<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor;

use AftersalesBundle\Http\Api\V1\Action\Aftersales;
use AftersalesBundle\Http\Api\V1\Action\Refund;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-073..075 / TC-04-01b
 */
class AftersalesDistributorFilterTest extends TestCase
{
    /**
     * TC-04-01b：售后/退款列表传入未授权 distributor_id 须拒绝而非静默移除 scope。
     * #given Aftersales@getAftersalesList 与 Refund@getRefundList
     * #when 检查 distributor_id 过滤逻辑
     * #then 须调用 AftersalesDistributorGate::assertAuthorizedDistributorFilter
     */
    public function testTc0401bUnauthorizedDistributorIdRejectedNotSilentlyDropped(): void
    {
        #given
        $aftersalesBody = $this->methodBody(Aftersales::class, 'getAftersalesList');
        $refundListBody = $this->methodBody(Refund::class, 'getRefundList');
        $refundExportBody = $this->methodBody(Refund::class, 'logExport');

        #when / #then
        $this->assertStringContainsString('assertAuthorizedDistributorFilter', $aftersalesBody);
        $this->assertStringContainsString('assertAuthorizedDistributorFilter', $refundListBody);
        $this->assertStringContainsString('assertAuthorizedDistributorFilter', $refundExportBody);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
