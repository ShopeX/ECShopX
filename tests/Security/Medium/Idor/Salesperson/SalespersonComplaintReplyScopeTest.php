<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Salesperson;

use SalespersonBundle\Http\Api\V1\Action\SalespersonController;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-101 / AC-04-01
 */
class SalespersonComplaintReplyScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-101：投诉回复须校验归属 auth company。
     */
    public function testTc0401ReplyComplaintScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(SalespersonController::class, 'replySalemanCustomerComplaints');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertStringNotContainsString('getInfoById', $body);
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
