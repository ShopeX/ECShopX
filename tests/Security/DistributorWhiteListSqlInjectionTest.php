<?php

declare(strict_types=1);

namespace Tests\Security;

use DistributionBundle\Services\DistributorWhiteListService;
use TestCase;

/**
 * ECX-10757：经销商白名单查询须参数绑定，禁止拼接 mobile 等用户输入。
 */
class DistributorWhiteListSqlInjectionTest extends TestCase
{
    public function testGetWhiteListDoesNotConcatenateMobileIntoSql(): void
    {
        $body = $this->methodBody(DistributorWhiteListService::class, 'getWhiteList');

        $this->assertStringNotContainsString(
            "mobile = '\" . \$filter['mobile']",
            $body,
            'mobile 不得通过字符串拼接拼入 SQL'
        );
        $this->assertStringNotContainsString(
            "implode(\"','\",\$filter['mobile'])",
            $body,
            'mobile 数组不得通过 implode 引号拼接拼入 SQL'
        );
        $this->assertTrue(
            str_contains($body, '?') || str_contains($body, 'createQueryBuilder'),
            'getWhiteList 须使用参数绑定或 QueryBuilder'
        );
    }

    public function testGetWhiteListBindsCompanyIdAndPagination(): void
    {
        $body = $this->methodBody(DistributorWhiteListService::class, 'getWhiteList');

        $this->assertStringContainsString('company_id', $body);
        $this->assertMatchesRegularExpression('/fetchAll\s*\(\s*\$sql\s*,\s*\$(?:listParams|params)/', $body);
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
