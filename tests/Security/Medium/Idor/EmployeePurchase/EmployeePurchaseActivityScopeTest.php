<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\EmployeePurchase;

use EmployeePurchaseBundle\Services\ActivitiesService;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-084 / AC-04-01
 */
class EmployeePurchaseActivityScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-084：员工内购 addActivityItems 须校验 activity 归属 auth company。
     */
    public function testTc0401AddActivityItemsScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(ActivitiesService::class, 'addActivityItems');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertMatchesRegularExpression(
            '/activity\[.company_id.\].*params\[.company_id.\]/s',
            $body
        );
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
