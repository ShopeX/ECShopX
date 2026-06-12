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

    public function testCustomDecorationSettingVersionCandidatesForShopIncludesV101Fallback(): void
    {
        $this->assertSame(
            ['shop_12', 'v1.0.1'],
            StoreHomePageService::customDecorationSettingVersionCandidates(12, 0)
        );
    }

    public function testCustomDecorationSettingVersionCandidatesForHeadquarters(): void
    {
        $this->assertSame(
            ['v1.0.1'],
            StoreHomePageService::customDecorationSettingVersionCandidates(0, 0)
        );
    }

    public function testDecorationTemplateNameCandidatesFallsBackToYykweishop(): void
    {
        $this->assertSame(
            ['my_theme', 'yykweishop'],
            StoreHomePageService::decorationTemplateNameCandidates(['template_name' => 'my_theme'])
        );
    }

    public function testPagesTemplateListSearchPlansForShop(): void
    {
        $this->assertSame(
            [
                ['distributor_id' => 8, 'weapp_pages' => 'distributor_index'],
                ['distributor_id' => 8, 'weapp_pages' => 'index'],
                ['distributor_id' => 0, 'weapp_pages' => 'index'],
            ],
            StoreHomePageService::pagesTemplateListSearchPlans(8)
        );
    }

    public function testBuildPageTemplateDetailFromTemplateConfList(): void
    {
        $detail = StoreHomePageService::buildPageTemplateDetailFromTemplateConfList([
            ['name' => 'slider', 'params' => ['name' => 'slider', 'base' => [], 'data' => []]],
            ['name' => 'plain', 'params' => ['title' => 'x']],
        ]);
        $this->assertCount(1, $detail['config']);
        $this->assertCount(2, $detail['list']);
    }

    public function testSafeDecodeWeappSettingParamsSupportsSerializeAndJson(): void
    {
        $payload = ['name' => 'page', 'base' => []];
        $this->assertSame($payload, StoreHomePageService::safeDecodeWeappSettingParams(serialize($payload)));
        $this->assertSame($payload, StoreHomePageService::safeDecodeWeappSettingParams(json_encode($payload)));
    }

    public function testBuildTemplateConfListFromWeappSettingEntities(): void
    {
        $payload = ['name' => 'page', 'base' => []];
        $entity = new class($payload) {
            private $params;

            public function __construct(array $params)
            {
                $this->params = serialize($params);
            }

            public function getId()
            {
                return 53453;
            }

            public function getTemplateName()
            {
                return 'yykweishop';
            }

            public function getCompanyId()
            {
                return 34;
            }

            public function getName()
            {
                return 'page';
            }

            public function getPageName()
            {
                return 'custom_106';
            }

            public function getParams()
            {
                return $this->params;
            }
        };

        $list = StoreHomePageService::buildTemplateConfListFromWeappSettingEntities([$entity], 34, 'yykweishop');
        $this->assertCount(1, $list);
        $this->assertSame('page', $list[0]['name']);
        $this->assertSame($payload, $list[0]['params']);
    }

}
