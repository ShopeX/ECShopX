<?php

use CompanysBundle\Services\CompanysStatisticsService;

class CompanysStatisticsWarningGoodsCountTest extends TestCase
{
    public function testWarningGoodsCountLabelZhCn(): void
    {
        app()->setLocale('zh-CN');
        $label = CompanysStatisticsService::formatWarningGoodsCountLabel(2, 6);
        $this->assertSame('2 (自营) / 6 (全部)  ', $label);
    }

    public function testWarningGoodsCountLabelEnCn(): void
    {
        app()->setLocale('en-CN');
        $label = CompanysStatisticsService::formatWarningGoodsCountLabel(2, 6);
        $this->assertSame('2 (Self-operated) / 6 (All)  ', $label);
    }
}
