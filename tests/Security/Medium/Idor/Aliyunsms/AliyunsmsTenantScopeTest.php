<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Aliyunsms;

use AliyunsmsBundle\Http\Api\V1\Action\Scene;
use AliyunsmsBundle\Http\Api\V1\Action\Task;
use AliyunsmsBundle\Services\SceneItemService;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-085,F-086,F-087 / AC-04-01
 */
class AliyunsmsTenantScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-086：场景启用须按 company_id 查找 scene item。
     */
    public function testTc0401EnableItemScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(SceneItemService::class, 'enableItem');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
    }

    /**
     * TC-04-01 / F-085：场景停用须按 company_id 查找 scene item。
     */
    public function testTc0401DisableItemScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(SceneItemService::class, 'disableItem');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
    }

    /**
     * TC-04-01 / F-087：短信任务详情须绑定 auth company_id。
     */
    public function testTc0401TaskGetInfoScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(Task::class, 'getInfo');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
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
