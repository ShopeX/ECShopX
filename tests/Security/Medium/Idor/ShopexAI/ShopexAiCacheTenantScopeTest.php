<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\ShopexAI;

use ShopexAIBundle\Http\Api\V1\Action\ArticleController;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-081 / AC-04-01
 */
class ShopexAiCacheTenantScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-081：AI 文章缓存键须含 tenant/user 身份。
     */
    public function testTc0401GenerateCacheKeyIncludesTenantAndUser(): void
    {
        #given
        $body = $this->methodBody(ArticleController::class, 'generateCacheKey');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertStringContainsString('operator_id', $body);
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
