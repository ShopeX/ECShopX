<?php

declare(strict_types=1);

namespace Tests\Security\Idor\WorkWechat;

use WorkWechatBundle\Services\WorkWechatService;
use TestCase;

/**
 * codex-security-03-high-idor T26 / F-034 / TC-03-15
 */
class WorkWechatCorpCacheScopeTest extends TestCase
{
    /**
     * TC-03-15 / F-034：保存企微配置须防止 corpid 缓存被异租户覆盖。
     * #given saveWorkWechatConfig 源码
     * #when 检查 corpid 租户绑定
     * #then 须调用 WorkWechatTenantScopeGuard
     */
    public function testTc0315SaveWorkWechatConfigScopesCorpidCacheByCompany(): void
    {
        #given
        $body = $this->methodBody(WorkWechatService::class, 'saveWorkWechatConfig');

        #when / #then
        $this->assertStringContainsString('WorkWechatTenantScopeGuard', $body);
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
