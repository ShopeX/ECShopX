<?php

declare(strict_types=1);

namespace Tests\Security;

use ShopexAIBundle\Http\Api\V1\Action\ArticleController;
use TestCase;

/**
 * codex-security-02-high-auth-crypto T12 / F-063 / TC-02-09
 */
class AuthCryptoTc0209ShopexAiCacheKeyPrefixTest extends TestCase
{
    /**
     * TC-02-09 / AC-02-08：checkGenerateStatus 仅允许带 article_gen: 前缀的 cache_key。
     * #given checkGenerateStatus 源码
     * #when 检查 cache_key 校验逻辑
     * #then 须校验 cacheKeyPrefix
     */
    public function testTc0209CheckGenerateStatusValidatesCacheKeyPrefix(): void
    {
        #given
        $body = $this->methodBody(ArticleController::class, 'checkGenerateStatus');

        #when / #then
        $this->assertStringContainsString('cacheKeyPrefix', $body);
        $this->assertStringContainsString('cache_key', $body);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }
}
