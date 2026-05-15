<?php

use EmployeePurchaseBundle\Services\StoreHomePageService;
use PHPUnit\Framework\TestCase;

final class StoreHomePageServiceInternalNameTest extends TestCase
{
    public function testInternalCustomizePageNameIsStable(): void
    {
        $this->assertSame(
            'ep_store_home_10_5',
            StoreHomePageService::internalCustomizePageName(10, 5)
        );
    }

    public function testUniqueInternalCustomizePageNameHasSuffix(): void
    {
        $name = StoreHomePageService::uniqueInternalCustomizePageName(10, 5);
        $this->assertMatchesRegularExpression(
            '/^ep_store_home_10_5_[0-9a-f]{8}$/',
            $name
        );
    }

    public function testUniqueInternalCustomizePageNameUsuallyDiffersBetweenCalls(): void
    {
        $a = StoreHomePageService::uniqueInternalCustomizePageName(10, 5);
        $b = StoreHomePageService::uniqueInternalCustomizePageName(10, 5);
        $this->assertNotSame($a, $b);
    }

    public function testWeappSettingPageNameForCustomizePage(): void
    {
        $this->assertNull(StoreHomePageService::weappSettingPageNameForCustomizePage(0));
        $this->assertNull(StoreHomePageService::weappSettingPageNameForCustomizePage(-1));
        $this->assertSame('custom_103', StoreHomePageService::weappSettingPageNameForCustomizePage(103));
    }

    public function testPageTemplateDetailHasNonEmptyList(): void
    {
        $this->assertFalse(StoreHomePageService::pageTemplateDetailHasNonEmptyList(null));
        $this->assertFalse(StoreHomePageService::pageTemplateDetailHasNonEmptyList([]));
        $this->assertFalse(StoreHomePageService::pageTemplateDetailHasNonEmptyList(['list' => []]));
        $this->assertTrue(StoreHomePageService::pageTemplateDetailHasNonEmptyList(['list' => [['x' => 1]]]));
    }
}
