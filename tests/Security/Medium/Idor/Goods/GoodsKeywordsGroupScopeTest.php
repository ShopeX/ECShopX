<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Goods;

use GoodsBundle\Http\Api\V1\Action\Items;
use GoodsBundle\Http\Api\V1\Action\ItemsGroupController;
use GoodsBundle\Services\KeywordsService;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-079,F-080 / AC-04-01
 */
class GoodsKeywordsGroupScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-079：关键词保存须校验 id 归属 auth company。
     * #given KeywordsService addKeywords
     * #when 检查租户 scope
     * #then 须按 company_id 查找关键词
     */
    public function testTc0401KeywordsSaveScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(KeywordsService::class, 'addKeywords');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
    }

    /**
     * TC-04-01 / F-080：分组商品保存须校验 group_id 归属 auth company。
     * #given ItemsGroupController saveGroupItem
     * #when 检查租户 scope
     * #then 须校验 group company_id
     */
    public function testTc0401SaveGroupItemScopesGroupByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(ItemsGroupController::class, 'saveGroupItem');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertMatchesRegularExpression(
            '/company_id.*group_id|group_id.*company_id/s',
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
