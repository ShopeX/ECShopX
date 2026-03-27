<?php

/**
 * 菜单导入多语言与兼容测试
 * 计划：.tasks/plans/menu-multilang-export-import.md TODO 5（RED）、TODO 6/7（GREEN）
 * 用例：TC-I1～TC-I6（新格式主表+多语言、旧格式仅主表、重复导入无残留、部分语种、空 name_lang、单条部分语种）
 * 当前仅编写测试，不实现多语言写入/删除。
 * RED 证据（需 DB 可用时）：TC-I1/I3/I4/I6 会因多语言表无数据或未先 deleteLang 而断言失败；TC-I2/I5 可能已绿（仅主表、不写多语言）。
 */

use CompanysBundle\Services\CommonLangModService;
use SuperAdminBundle\Services\ShopMenuService;

class MenuImportTest extends TestCase
{
    /** @var string[] config 语种，与 config/langue.php 一致 */
    private const CONFIG_LANGS = ['zh-CN', 'en-CN', 'ar-SA'];

    /**
     * 构造一条可供 uploadMenus 使用的最小合法菜单行（可含 name_lang）
     */
    private function buildUploadMenuRow(array $overrides = []): array
    {
        $defaults = [
            'shopmenu_id' => 1,
            'version'     => 1,
            'company_id'  => 0,
            'alias_name'  => 'import_test_' . uniqid(),
            'name'        => 'Test Menu',
            'url'         => '/test',
            'sort'        => 1,
            'pid'         => 0,
            'apis'        => '',
            'icon'        => '',
            'is_show'     => true,
            'is_menu'     => true,
            'disabled'    => false,
            'menu_type'   => ['all'],
        ];
        return array_merge($defaults, $overrides);
    }

    /**
     * 导入后按 version+company_id 取第一条菜单的 shopmenu_id（用于单条断言）
     */
    private function getFirstInsertedShopmenuId(ShopMenuService $service, int $version, int $companyId): ?int
    {
        $res = $service->shopMenuRepository->lists(
            ['version' => $version, 'company_id' => $companyId],
            'shopmenu_id',
            1,
            1,
            ['shopmenu_id' => 'ASC']
        );
        $list = $res['list'] ?? [];
        return isset($list[0]['shopmenu_id']) ? (int) $list[0]['shopmenu_id'] : null;
    }

    /**
     * 按 alias_name 查找导入后的 shopmenu_id
     */
    private function getShopmenuIdByAlias(ShopMenuService $service, int $version, int $companyId, string $aliasName): ?int
    {
        $res = $service->shopMenuRepository->getInfo([
            'version'    => $version,
            'company_id' => $companyId,
            'alias_name' => $aliasName,
        ]);
        return isset($res['shopmenu_id']) ? (int) $res['shopmenu_id'] : null;
    }

    /**
     * TC-I1：新格式文件，含 3 语种 name_lang。
     * #given 新格式 list 含 name 与 name_lang(zh-CN, en-CN, ar-SA)
     * #when 调用 uploadMenus 导入
     * #then 主表 name 正确；3 个多语言表均有对应 data_id 的 attribute_value（当前 RED：未实现 saveLang）
     */
    public function testImportNewFormatWithThreeLanguagesWritesMainTableAndMultilangTcI1(): void
    {
        $companyId = 0;
        $version   = 91; // SMALLINT 范围内，避免与业务 version 1-7 冲突
        $row = $this->buildUploadMenuRow([
            'version'   => $version,
            'company_id'=> $companyId,
            'name'      => '首页',
            'name_lang' => [
                'zh-CN' => '首页',
                'en-CN' => 'Home',
                'ar-SA' => 'الرئيسية',
            ],
        ]);

        $service = new ShopMenuService();
        $service->uploadMenus([$row], $companyId);

        $sid = $this->getFirstInsertedShopmenuId($service, $version, $companyId);
        $this->assertNotNull($sid, '导入后应能查到菜单 (TC-I1)');

        $mainMenu = $service->shopMenuRepository->getInfo(['shopmenu_id' => $sid]);
        $this->assertSame('首页', $mainMenu['name'] ?? null, '主表 name 应为 list[].name (TC-I1)');

        $commonLang = new CommonLangModService();
        foreach (self::CONFIG_LANGS as $lang) {
            $value = $commonLang->getFieldByLangue($companyId, $lang, 'name', $sid, 'shop_menu', 'shop_menu');
            $this->assertSame($row['name_lang'][$lang], $value, "多语言表 {$lang} 应有对应 data_id 的 attribute_value (TC-I1)");
        }
    }

    /**
     * TC-I2：旧格式文件（无 name_lang）。
     * #given 旧格式 list 仅有 name，无 name_lang
     * #when 调用 uploadMenus 导入
     * #then 导入成功，主表 name 正确，多语言表不写入该批（当前可实现：未写多语言即满足）
     */
    public function testImportOldFormatWithoutNameLangWritesOnlyMainTableTcI2(): void
    {
        $companyId = 0;
        $version   = 92; // SMALLINT 范围内
        $row = $this->buildUploadMenuRow([
            'version'   => $version,
            'company_id'=> $companyId,
            'name'      => '旧格式菜单',
        ]);
        unset($row['name_lang']);

        $service = new ShopMenuService();
        $service->uploadMenus([$row], $companyId);

        $sid = $this->getFirstInsertedShopmenuId($service, $version, $companyId);
        $this->assertNotNull($sid, '导入后应能查到菜单 (TC-I2)');

        $mainMenu = $service->shopMenuRepository->getInfo(['shopmenu_id' => $sid]);
        $this->assertSame('旧格式菜单', $mainMenu['name'] ?? null, '主表 name 应与文件中 name 一致 (TC-I2)');

        $commonLang = new CommonLangModService();
        foreach (self::CONFIG_LANGS as $lang) {
            $value = $commonLang->getFieldByLangue($companyId, $lang, 'name', $sid, 'shop_menu', 'shop_menu');
            $this->assertSame('', $value, '旧格式导入时多语言表不写入该批 (TC-I2)');
        }
    }

    /**
     * TC-I3：先导入新格式，再导入同 version+company 的另一新格式。
     * #given 第一次导入含 name_lang，第二次导入同 version+company 不同 name_lang
     * #when 第二次导入完成
     * #then 无第一次的多语言残留，以第二次文件为准（当前 RED：未先 deleteLang 再写）
     */
    public function testImportSameVersionCompanyTwiceHasNoResidualMultilangTcI3(): void
    {
        $companyId = 0;
        $version   = 93; // SMALLINT 范围内
        $alias     = 'import_tc3_' . uniqid();

        $firstRow = $this->buildUploadMenuRow([
            'version'    => $version,
            'company_id' => $companyId,
            'alias_name' => $alias,
            'name'       => '第一版',
            'name_lang'  => ['zh-CN' => '第一版', 'en-CN' => 'First', 'ar-SA' => ''],
        ]);
        $secondRow = $this->buildUploadMenuRow([
            'version'    => $version,
            'company_id' => $companyId,
            'alias_name' => $alias,
            'name'       => '第二版',
            'name_lang'  => ['zh-CN' => '第二版', 'en-CN' => 'Second', 'ar-SA' => ''],
        ]);

        $service = new ShopMenuService();
        $service->uploadMenus([$firstRow], $companyId);
        $sid = $this->getShopmenuIdByAlias($service, $version, $companyId, $alias);
        $this->assertNotNull($sid);

        $service->uploadMenus([$secondRow], $companyId);
        $sid2 = $this->getShopmenuIdByAlias($service, $version, $companyId, $alias);
        $this->assertNotNull($sid2, '第二次导入后应有一条菜单 (TC-I3)');

        $mainMenu = $service->shopMenuRepository->getInfo(['shopmenu_id' => $sid2]);
        $this->assertSame('第二版', $mainMenu['name'] ?? null, '主表以第二次为准 (TC-I3)');

        $commonLang = new CommonLangModService();
        $zhValue = $commonLang->getFieldByLangue($companyId, 'zh-CN', 'name', $sid2, 'shop_menu', 'shop_menu');
        $this->assertSame('第二版', $zhValue, '第二次导入后无第一次多语言残留，zh-CN 应以第二次文件为准 (TC-I3)');
    }

    /**
     * TC-I4：新格式但某条 name_lang 仅含 zh-CN。
     * #given list 一项 name_lang 仅含 zh-CN
     * #when 调用 uploadMenus 导入
     * #then 主表 name 取自 list[].name；仅 zh-CN 多语言表有该条；en-CN/ar-SA 无该 data_id（当前 RED：未实现 saveLang）
     */
    public function testImportNewFormatWithOnlyZhCnInNameLangWritesOnlyZhCnTcI4(): void
    {
        $companyId = 0;
        $version   = 94; // SMALLINT 范围内
        $row = $this->buildUploadMenuRow([
            'version'   => $version,
            'company_id'=> $companyId,
            'name'      => '概况',
            'name_lang' => ['zh-CN' => '概况'],
        ]);

        $service = new ShopMenuService();
        $service->uploadMenus([$row], $companyId);

        $sid = $this->getFirstInsertedShopmenuId($service, $version, $companyId);
        $this->assertNotNull($sid, '导入后应能查到菜单 (TC-I4)');

        $mainMenu = $service->shopMenuRepository->getInfo(['shopmenu_id' => $sid]);
        $this->assertSame('概况', $mainMenu['name'] ?? null, '主表 name 取自 list[].name (TC-I4)');

        $commonLang = new CommonLangModService();
        $zh = $commonLang->getFieldByLangue($companyId, 'zh-CN', 'name', $sid, 'shop_menu', 'shop_menu');
        $this->assertSame('概况', $zh, '仅 zh-CN 多语言表应有该条 (TC-I4)');
        $en = $commonLang->getFieldByLangue($companyId, 'en-CN', 'name', $sid, 'shop_menu', 'shop_menu');
        $this->assertSame('', $en, 'en-CN 无该 data_id (TC-I4)');
        $ar = $commonLang->getFieldByLangue($companyId, 'ar-SA', 'name', $sid, 'shop_menu', 'shop_menu');
        $this->assertSame('', $ar, 'ar-SA 无该 data_id (TC-I4)');
    }

    /**
     * TC-I5：新格式但 name_lang 为空对象 {}。
     * #given list 一项 name_lang 为 {}
     * #when 调用 uploadMenus 导入
     * #then 主表 name 用 list[].name；多语言表不写入该条（当前可实现：未写即满足）
     */
    public function testImportNewFormatWithEmptyNameLangObjectWritesOnlyMainTableTcI5(): void
    {
        $companyId = 0;
        $version   = 95; // SMALLINT 范围内
        $row = $this->buildUploadMenuRow([
            'version'   => $version,
            'company_id'=> $companyId,
            'name'      => '仅主表名称',
            'name_lang' => [],
        ]);

        $service = new ShopMenuService();
        $service->uploadMenus([$row], $companyId);

        $sid = $this->getFirstInsertedShopmenuId($service, $version, $companyId);
        $this->assertNotNull($sid, '导入后应能查到菜单 (TC-I5)');

        $mainMenu = $service->shopMenuRepository->getInfo(['shopmenu_id' => $sid]);
        $this->assertSame('仅主表名称', $mainMenu['name'] ?? null, '主表 name 用 list[].name (TC-I5)');

        $commonLang = new CommonLangModService();
        foreach (self::CONFIG_LANGS as $lang) {
            $value = $commonLang->getFieldByLangue($companyId, $lang, 'name', $sid, 'shop_menu', 'shop_menu');
            $this->assertSame('', $value, 'name_lang 为空对象时多语言表不写入该条 (TC-I5)');
        }
    }

    /**
     * TC-I6：单条菜单、name_lang 含部分语种。
     * #given 单条 list，name_lang 仅含 zh-CN 与 en-CN
     * #when 调用 uploadMenus 导入
     * #then 仅存在的语种写入多语言表（当前 RED：未实现 saveLang）
     */
    public function testImportSingleMenuWithPartialNameLangWritesOnlyPresentLangsTcI6(): void
    {
        $companyId = 0;
        $version   = 96; // SMALLINT 范围内
        $row = $this->buildUploadMenuRow([
            'version'   => $version,
            'company_id'=> $companyId,
            'name'      => '边界单条',
            'name_lang' => ['zh-CN' => '边界单条', 'en-CN' => 'Single Partial'],
        ]);

        $service = new ShopMenuService();
        $service->uploadMenus([$row], $companyId);

        $sid = $this->getFirstInsertedShopmenuId($service, $version, $companyId);
        $this->assertNotNull($sid, '导入后应能查到菜单 (TC-I6)');

        $mainMenu = $service->shopMenuRepository->getInfo(['shopmenu_id' => $sid]);
        $this->assertSame('边界单条', $mainMenu['name'] ?? null, '主表 name 用 list[].name (TC-I6)');

        $commonLang = new CommonLangModService();
        $this->assertSame('边界单条', $commonLang->getFieldByLangue($companyId, 'zh-CN', 'name', $sid, 'shop_menu', 'shop_menu'), 'zh-CN 应写入 (TC-I6)');
        $this->assertSame('Single Partial', $commonLang->getFieldByLangue($companyId, 'en-CN', 'name', $sid, 'shop_menu', 'shop_menu'), 'en-CN 应写入 (TC-I6)');
        $this->assertSame('', $commonLang->getFieldByLangue($companyId, 'ar-SA', 'name', $sid, 'shop_menu', 'shop_menu'), 'ar-SA 未提供则不写入 (TC-I6)');
    }
}
