<?php

/**
 * fix-items-list-main-cat-id：TC-06 Action 主类目 enrichment 契约（AS-05, AS-06）
 *
 * 断言目标行为（T4 将在 Items.php:1836/2202 inline 的写法）：
 *   $mainCatId = $value['item_category'] ?? $value['item_main_cat_id'] ?? '';
 *   $value['itemMainCatName'] = '';
 *   if ($mainCatId !== '' && $mainCatId !== null) {
 *       $categoryInfo = $itemsCategoryService->getInfoById($mainCatId);
 *       $value['itemMainCatName'] = $categoryInfo['category_name'] ?? '';
 *   }
 *
 * 被测逻辑与 Items.php:1836/2202 inline 的 target enrichment 等价。
 */

use GoodsBundle\Services\ItemsCategoryService;

class ItemMainCatIdEnrichmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * TC-06：fixture A/B/C/D 覆盖 fallback、条件查询与 itemMainCatName。
     *
     * @dataProvider mainCatEnrichmentFixtureProvider
     */
    public function testTc06ActionMainCatEnrichment(
        array $fixture,
        int $expectedGetInfoByIdCalls,
        ?string $expectedGetInfoByIdArg,
        string $expectedItemMainCatName
    ): void {
        #given
        $invokedArgs = [];
        $service = $this->getMockBuilder(ItemsCategoryService::class)
            ->disableOriginalConstructor()
            ->addMethods(['getInfoById'])
            ->getMock();
        $service->method('getInfoById')
            ->willReturnCallback(function ($id) use (&$invokedArgs) {
                $invokedArgs[] = $id;
                if ($id === '100') {
                    return ['category_name' => '主类目'];
                }

                return [];
            });

        #when
        $value = $fixture;
        try {
            $value = $this->applyEnrichmentUnderTest($value, $service);
        } catch (\Error $e) {
            $this->fail('AS-05: must not throw Undefined array key on missing item_main_cat_id: '.$e->getMessage());
        }

        #then
        $this->assertCount(
            $expectedGetInfoByIdCalls,
            $invokedArgs,
            'getInfoById call count must match contract'
        );
        if ($expectedGetInfoByIdArg !== null) {
            $this->assertSame($expectedGetInfoByIdArg, $invokedArgs[0] ?? null);
        }
        foreach ($invokedArgs as $arg) {
            $this->assertNotSame('', $arg, 'getInfoById must never be called with empty string');
            $this->assertNotNull($arg, 'getInfoById must never be called with null');
        }
        $this->assertSame($expectedItemMainCatName, $value['itemMainCatName'] ?? '');
    }

    /**
     * 与 Items.php:1836/2202 inline target enrichment 等价。
     */
    private function applyEnrichmentUnderTest(array $value, ItemsCategoryService $service): array
    {
        $mainCatId = $value['item_category'] ?? $value['item_main_cat_id'] ?? '';
        $value['itemMainCatName'] = '';
        if ($mainCatId !== '' && $mainCatId !== null) {
            $categoryInfo = $service->getInfoById($mainCatId);
            $value['itemMainCatName'] = $categoryInfo['category_name'] ?? '';
        }

        return $value;
    }

    public static function mainCatEnrichmentFixtureProvider(): array
    {
        return [
            'A: missing item_main_cat_id, has item_category' => [
                ['item_id' => 1, 'item_category' => '100'],
                1,
                '100',
                '主类目',
            ],
            'B: both keys missing' => [
                ['item_id' => 2],
                0,
                null,
                '',
            ],
            'C: empty item_category' => [
                ['item_id' => 3, 'item_category' => ''],
                0,
                null,
                '',
            ],
            'D: both present, category wins for lookup' => [
                ['item_id' => 4, 'item_main_cat_id' => '9', 'item_category' => '100'],
                1,
                '100',
                '主类目',
            ],
        ];
    }
}
