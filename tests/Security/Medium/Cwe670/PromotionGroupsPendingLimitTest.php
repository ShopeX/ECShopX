<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Cwe670;

use PromotionsBundle\Services\PromotionGroupsActivityService;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-156 / TC-04-10
 */
class PromotionGroupsPendingLimitTest extends TestCase
{
    /**
     * TC-04-10 / F-156：同一用户同一拼团活动仅允许一笔待成团订单。
     * #given PromotionGroupsActivityService::checkCreateGroupOrder
     * #when 检查 pending 限制分支
     * #then 须判断 $waitGroupsOrder 而非错误变量 $waitGroups
     */
    public function testTc0410CheckCreateGroupOrderUsesWaitGroupsOrderVariable(): void
    {
        #given
        $body = $this->methodBody(PromotionGroupsActivityService::class, 'checkCreateGroupOrder');

        #when / #then
        $this->assertStringContainsString('!empty($waitGroupsOrder)', $body);
        $this->assertStringNotContainsString('!empty($waitGroups)', $body);
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
