<?php

/**
 * 计划：.tasks/plans/sync-operator-distributor-name.md — TC-P01、TC-P02
 */

use CompanysBundle\Support\OperatorDistributorIdsPatterns;

class OperatorDistributorIdsPatternsTest extends TestCase
{
    /** TC-P01 */
    public function testForDistributorId12ReturnsFourPatternsWithoutBarePrefixFalseMatchOn129(): void
    {
        #given
        $distributorId = 12;
        $haystack129 = '"distributor_id":129';

        #when
        $patterns = OperatorDistributorIdsPatterns::forDistributorId($distributorId);

        #then
        $expected = [
            '"distributor_id":"12"',
            '"distributor_id":12}',
            '"distributor_id":12,',
            '"distributor_id":12]',
        ];
        foreach ($expected as $pattern) {
            $this->assertContains($pattern, $patterns, "missing pattern: {$pattern}");
        }

        $this->assertNotContains('"distributor_id":12', $patterns, 'must not use bare numeric prefix without delimiter suffix');

        foreach ($patterns as $pattern) {
            if ($pattern === '"distributor_id":"12"') {
                continue;
            }
            $lastChar = substr($pattern, -1);
            $this->assertContains(
                $lastChar,
                ['}', ',', ']'],
                "non-quoted numeric pattern must end with delimiter suffix: {$pattern}"
            );
        }

        foreach ($patterns as $pattern) {
            $this->assertFalse(
                str_contains($haystack129, $pattern),
                "pattern {$pattern} must not match distributor_id 129 via contains"
            );
        }
    }

    /** TC-P02 */
    public function testForDistributorIdEmptyStringReturnsEmptyArray(): void
    {
        #given
        $distributorId = '';

        #when
        $patterns = OperatorDistributorIdsPatterns::forDistributorId($distributorId);

        #then
        $this->assertSame([], $patterns);
    }
}
