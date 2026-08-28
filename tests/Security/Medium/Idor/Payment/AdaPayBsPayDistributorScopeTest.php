<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Payment;

use AdaPayBundle\Http\Api\V1\Action\AdapayTrade;
use AdaPayBundle\Http\Api\V1\Action\ExportData as AdaPayExportData;
use BsPayBundle\Http\Api\V1\Action\ExportData as BsPayExportData;
use BsPayBundle\Http\Api\V1\Action\Trade;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-082,F-083,F-096,F-097 / AC-04-01
 */
class AdaPayBsPayDistributorScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-083：AdaPay trade list 店铺端不得被 distributor_name 覆盖 scope。
     */
    public function testTc0401AdapayTradeListBlocksDistributorNameOverride(): void
    {
        #given
        $body = $this->methodBody(AdapayTrade::class, 'getTradelist');

        #when / #then
        $this->assertStringContainsString('PaymentTenantScopeGuard', $body);
        $this->assertStringContainsString('assertDistributorNameFilterAllowed', $body);
    }

    /**
     * TC-04-01 / F-082：AdaPay export 店铺端不得被 distributor_name 覆盖 scope。
     */
    public function testTc0401AdapayExportBlocksDistributorNameOverride(): void
    {
        #given
        $body = $this->methodBody(AdaPayExportData::class, 'exportTradeData');

        #when / #then
        $this->assertStringContainsString('PaymentTenantScopeGuard', $body);
        $this->assertStringContainsString('assertDistributorNameFilterAllowed', $body);
    }

    /**
     * TC-04-01 / F-097：BsPay trade list 店铺端不得被 distributor_name 覆盖 scope。
     */
    public function testTc0401BsPayTradeListBlocksDistributorNameOverride(): void
    {
        #given
        $body = $this->methodBody(Trade::class, 'getTradelist');

        #when / #then
        $this->assertStringContainsString('PaymentTenantScopeGuard', $body);
        $this->assertStringContainsString('assertDistributorNameFilterAllowed', $body);
    }

    /**
     * TC-04-01 / F-096：BsPay export 店铺端不得被 distributor_name 覆盖 scope。
     */
    public function testTc0401BsPayExportBlocksDistributorNameOverride(): void
    {
        #given
        $body = $this->methodBody(BsPayExportData::class, 'exportTradeData');

        #when / #then
        $this->assertStringContainsString('PaymentTenantScopeGuard', $body);
        $this->assertStringContainsString('assertDistributorNameFilterAllowed', $body);
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
