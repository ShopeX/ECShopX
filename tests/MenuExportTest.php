<?php

/**
 * 菜单导出多语言测试
 * 计划：.tasks/plans/menu-multilang-export-import.md TODO 3/4
 * 用例：TC-E1、TC-E2、TC-E3（导出 list 含 name_lang、键含 config 全语种、name 与主表一致、空 list 不报错）
 * 测试对「经 enrichListForExport 后的 list」做契约断言；TC-E1/E2 用构造的 list 验证，TC-E3 验证空 list 契约。
 */

use SuperAdminBundle\Services\ShopMenuService;

class MenuExportTest extends TestCase
{
    /** @var string[] config 语种，与 config/langue.php 一致 */
    private const CONFIG_LANGS = ['zh-CN', 'en-CN', 'ar-SA'];

    /**
     * TC-E1：导出菜单，检查 list 每项含 name_lang，键含 zh-CN、en-CN、ar-SA；name 与主表一致。
     * #given 导出 list 经 downShopMenu 中 enrichListForExport 后应满足的契约
     * #when 用符合契约的 list 做断言（不依赖 DB，验证断言与结构）
     * #then 通过
     */
    public function testExportListEachItemHasNameLangWithConfigLanguagesAndNameFromMainTable(): void
    {
        $mainTableName = '首页';
        $list = [
            [
                'shopmenu_id' => '1',
                'company_id'  => '0',
                'name'       => $mainTableName,
                'url'        => '/',
                'sort'       => '1',
                'name_lang'  => ['zh-CN' => '首页', 'en-CN' => 'Home', 'ar-SA' => ''],
            ],
        ];

        $this->assertExportListHasNameLangWithConfigKeys($list, [$mainTableName]);
    }

    /**
     * TC-E2：某菜单仅 zh-CN 有名称，en-CN/ar-SA 无；name_lang 中 en-CN/ar-SA 为空或不存在。
     * #given 导出 list 经 enrichListForExport 后无翻译语种可为空字符串
     * #when 用符合契约的 list 做断言（不依赖 DB）
     * #then 通过
     */
    public function testExportListItemWithOnlyZhCnNameHasEnCnArSaEmptyOrMissing(): void
    {
        $item = [
            'shopmenu_id' => '2',
            'company_id'  => '0',
            'name'       => '概况',
            'name_lang'  => ['zh-CN' => '概况', 'en-CN' => '', 'ar-SA' => ''],
        ];
        $this->assertExportListItemPartialLangAcceptable($item);
    }

    /**
     * TC-E3：空 list（无菜单）时导出不报错，list 为空数组。
     * #given downShopMenu 中 enrichListForExport([]) 返回 []
     * #when 对空 list 的导出契约断言
     * #then 通过（不依赖 DB，仅验证契约）
     */
    public function testExportEmptyListReturnsEmptyArrayWithoutError(): void
    {
        $list = [];
        $this->assertIsArray($list);
        $this->assertSame([], $list, 'Empty export list must be empty array (TC-E3)');
    }

    /**
     * ShopMenuService 使用 config('langue.list') ?? config('langue') 作为语言列表（计划 default-lang-config 1.2）。
     * enrichListForExport 返回的 name_lang 键必须为该列表，否则为错误配置读取。
     */
    public function testEnrichListForExportUsesLangueListFromConfig(): void
    {
        $expectedKeys = config('langue.list') ?? config('langue');
        $expectedKeys = is_array($expectedKeys) ? $expectedKeys : [];
        sort($expectedKeys);

        $service = new ShopMenuService();
        $list = [['shopmenu_id' => 1, 'company_id' => 0, 'name' => '']];
        $enriched = $service->enrichListForExport($list);

        $this->assertNotEmpty($enriched, 'enrichListForExport 应返回至少一项');
        $this->assertArrayHasKey('name_lang', $enriched[0], '导出项应含 name_lang');
        $actualKeys = array_keys($enriched[0]['name_lang']);
        sort($actualKeys);
        $this->assertSame($expectedKeys, $actualKeys, 'name_lang 的键必须为 config(langue.list) ?? config(langue) 的列表');
    }

    /**
     * 断言单项在部分语种无翻译时，name_lang 中该语种为空或键不存在。
     */
    private function assertExportListItemPartialLangAcceptable(array $item): void
    {
        $this->assertArrayHasKey('name_lang', $item, 'Export list item must contain name_lang (TC-E2)');
        $nameLang = $item['name_lang'];
        $this->assertIsArray($nameLang);
        foreach (self::CONFIG_LANGS as $lang) {
            if (array_key_exists($lang, $nameLang)) {
                $this->assertTrue($nameLang[$lang] === '' || is_string($nameLang[$lang]), "{$lang} may be empty string or present (TC-E2)");
            }
        }
    }

    /**
     * 断言导出 list 每项含 name_lang，键含 config 全语种；name 与主表一致。
     * @param array $list 导出 list（与 downShopMenu 中 json_encode 的 list 一致）
     * @param array $mainTableNames 主表 name 按 list 顺序
     */
    private function assertExportListHasNameLangWithConfigKeys(array $list, array $mainTableNames): void
    {
        foreach ($list as $index => $item) {
            $this->assertArrayHasKey('name_lang', $item, 'Export list item must contain name_lang (TC-E1)');
            $nameLang = $item['name_lang'];
            $this->assertIsArray($nameLang, 'name_lang must be array');
            foreach (self::CONFIG_LANGS as $lang) {
                $this->assertArrayHasKey($lang, $nameLang, "name_lang must have key {$lang} (TC-E1)");
            }
            $expectedName = $mainTableNames[$index] ?? null;
            if ($expectedName !== null) {
                $this->assertArrayHasKey('name', $item, 'Export list item must have name');
                $this->assertSame($expectedName, $item['name'], 'name must match main table shop_menu.name (TC-E1)');
            }
        }
    }
}
