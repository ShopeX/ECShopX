<?php

/**
 * CNVD-2026-45822：ItemsCategoryRepository 对 columns / order_by / filter 白名单化。
 */

use Doctrine\ORM\Mapping\ClassMetadata;
use GoodsBundle\Entities\ItemsCategory;
use GoodsBundle\Repositories\ItemsCategoryRepository;

class ItemsCategorySqlInjectionSanitizeTest extends TestCase
{
    private function createRepositoryWithoutDb(): ItemsCategoryRepository
    {
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $metadata = new ClassMetadata(ItemsCategory::class);
        return new ItemsCategoryRepository($em, $metadata);
    }

    private function invokePrivate($object, string $method, array $args = [])
    {
        $ref = new \ReflectionClass($object);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($object, $args);
    }

    public function testSanitizeColumnsDropsSubqueryInjection(): void
    {
        $repo = $this->createRepositoryWithoutDb();
        $out = $this->invokePrivate($repo, 'sanitizeColumns', [
            'category_id,category_name,parent_id,category_level,(SELECT 1) AS canary',
        ]);
        $this->assertSame('category_id,category_name,parent_id,category_level', $out);
        $this->assertStringNotContainsString('SELECT', $out);
        $this->assertStringNotContainsString('canary', $out);
    }

    public function testSanitizeColumnsWithAlias(): void
    {
        $repo = $this->createRepositoryWithoutDb();
        $out = $this->invokePrivate($repo, 'sanitizeColumns', [
            'category_id,(SELECT database()) AS db_name',
            'p',
        ]);
        $this->assertSame('p.category_id', $out);
    }

    public function testSanitizeColumnsFallsBackToStarWhenAllIllegal(): void
    {
        $repo = $this->createRepositoryWithoutDb();
        $out = $this->invokePrivate($repo, 'sanitizeColumns', [
            '(SELECT 1) AS canary',
        ]);
        $this->assertSame('*', $out);
    }

    public function testSanitizeOrderByRejectsInjectedDirection(): void
    {
        $repo = $this->createRepositoryWithoutDb();
        $out = $this->invokePrivate($repo, 'sanitizeOrderBy', [[
            'sort' => 'DESC, (SELECT 1)',
            'category_id' => 'ASC',
            'evil_col' => 'DESC',
        ]]);
        $this->assertSame(['category_id' => 'ASC'], $out);
    }

    public function testFilterSkipsInjectedColumnKeys(): void
    {
        $repo = $this->createRepositoryWithoutDb();
        $qb = $this->getMockBuilder(\Doctrine\DBAL\Query\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['andWhere', 'expr'])
            ->getMock();
        $expr = $this->getMockBuilder(\Doctrine\DBAL\Query\Expression\ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['eq', 'literal'])
            ->getMock();

        $qb->method('expr')->willReturn($expr);
        $expr->expects($this->once())->method('eq')
            ->with('category_id', $this->anything())
            ->willReturn('category_id = ?');
        $expr->method('literal')->willReturnCallback(function ($v) {
            return "'".$v."'";
        });
        $qb->expects($this->once())->method('andWhere')->willReturnSelf();

        $this->invokePrivate($repo, '_filter', [[
            'category_id = 1 OR 1=1 OR category_id' => 'x',
            'category_id' => 1,
        ], $qb]);
    }
}
