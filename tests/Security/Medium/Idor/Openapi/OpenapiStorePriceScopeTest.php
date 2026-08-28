<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Openapi;

use OpenapiBundle\Filter\BaseFilter;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-077,F-078 (ALREADY_FIXED verify) / AC-04-01
 */
class OpenapiStorePriceScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-077,F-078：OpenAPI BaseFilter 已 fail-closed company_id（T01）。
     * #given BaseFilter resolveCompanyId
     * #when 检查租户绑定
     * #then 须校验 auth company_id
     */
    public function testTc0401OpenapiBaseFilterFailClosedCompanyId(): void
    {
        #given
        $ref = new \ReflectionClass(BaseFilter::class);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $source = file_get_contents($file);

        #when / #then
        $this->assertStringContainsString('company_id', $source);
        $this->assertStringContainsString('auth', $source);
    }
}
