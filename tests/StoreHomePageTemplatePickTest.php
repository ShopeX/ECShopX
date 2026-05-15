<?php

use EmployeePurchaseBundle\Services\StoreHomePageService;
use PHPUnit\Framework\TestCase;

final class StoreHomePageTemplatePickTest extends TestCase
{
    public function testPicksFirstEnabledByTemplateName(): void
    {
        $list = [
            ['template_name' => 'other', 'pages_template_id' => 1, 'status' => 1],
            ['template_name' => 'yyk', 'pages_template_id' => 99, 'status' => 1],
            ['template_name' => 'yyk', 'pages_template_id' => 100, 'status' => 0],
        ];
        $row = StoreHomePageService::pickResolvedPagesTemplateRow($list, 'yyk');
        $this->assertNotNull($row);
        $this->assertSame(99, (int) $row['pages_template_id']);
    }

    public function testFallsBackToFirstMatchWhenNoneEnabled(): void
    {
        $list = [
            ['template_name' => 'yyk', 'pages_template_id' => 7, 'status' => 0],
        ];
        $row = StoreHomePageService::pickResolvedPagesTemplateRow($list, 'yyk');
        $this->assertNotNull($row);
        $this->assertSame(7, (int) $row['pages_template_id']);
    }

    public function testReturnsNullWhenNoMatch(): void
    {
        $this->assertNull(StoreHomePageService::pickResolvedPagesTemplateRow([], 'yyk'));
        $this->assertNull(StoreHomePageService::pickResolvedPagesTemplateRow(
            [['template_name' => 'x', 'pages_template_id' => 1, 'status' => 1]],
            'yyk'
        ));
    }

    public function testEmptyTemplateNameReturnsNull(): void
    {
        $this->assertNull(StoreHomePageService::pickResolvedPagesTemplateRow(
            [['template_name' => 'yyk', 'pages_template_id' => 1, 'status' => 1]],
            ''
        ));
    }
}
