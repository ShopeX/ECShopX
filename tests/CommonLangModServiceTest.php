<?php

use CompanysBundle\Services\CommonLangModService;

/**
 * 单元测试：CommonLangModService::deleteLang 按语种删除扩展（RED）
 * 计划：.tasks/plans/menu-multilang-export-import.md TODO 1
 *
 * 语义：deleteLang($companyId, $tableName, $dataId, $module [, $langue = null])
 * - 传入 $langue 时：使用该语种对应的 repository 删除指定语种记录
 * - 不传 $langue 时：保持现有行为（使用 getLang()）
 */
class CommonLangModServiceTest extends TestCase
{
    /**
     * RED：传入 $langue 时，应使用该语种对应的 repository 删除，仅删指定语种记录。
     * #given 当前请求语种为 zh-CN（getLang()），但调用时传入 $langue = 'en-CN'
     * #when 调用 deleteLang($companyId, $tableName, $dataId, $module, 'en-CN')
     * #then getLangMapRepository('en-CN') 被调用（而非 getLang() 的 zh-CN）
     * 当前为 RED：deleteLang 尚无第五参数，实现仍用 getLang()，故会调用 getLangMapRepository('zh-CN')，与预期 'en-CN' 不符。
     */
    public function testDeleteLangWithLangueParameterUsesSpecifiedLangueRepository(): void
    {
        $companyId = 1;
        $tableName = 'shop_menu';
        $dataId = 10;
        $module = 'shop_menu';
        $languePassed = 'en-CN';

        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'zh-CN']));

        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['deleteBy'])
            ->getMock();
        $mockRepo->expects($this->once())
            ->method('deleteBy')
            ->with($this->callback(function (array $filter) use ($companyId, $tableName, $module, $dataId) {
                return isset($filter['company_id'], $filter['table_name'], $filter['module_name'], $filter['data_id'])
                    && $filter['company_id'] === $companyId
                    && $filter['table_name'] === $tableName
                    && $filter['module_name'] === $module
                    && $filter['data_id'] === $dataId;
            }))
            ->willReturn(true);

        $service = $this->getMockBuilder(CommonLangModService::class)
            ->onlyMethods(['getLangMapRepository'])
            ->getMock();
        $service->expects($this->once())
            ->method('getLangMapRepository')
            ->with($languePassed)
            ->willReturn($mockRepo);

        $service->deleteLang($companyId, $tableName, $dataId, $module, $languePassed);
    }

    /**
     * 不传 $langue 时，应使用 getLang() 得到的当前语种对应的 repository 删除（保持现有行为）。
     * #given 请求语种为 en-CN
     * #when 调用 deleteLang($companyId, $tableName, $dataId, $module)（仅 4 个参数）
     * #then getLangMapRepository('en-CN') 被调用
     */
    public function testDeleteLangWithoutLangueParameterUsesGetLang(): void
    {
        $companyId = 1;
        $tableName = 'shop_menu';
        $dataId = 10;
        $module = 'shop_menu';

        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'en-CN']));

        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['deleteBy'])
            ->getMock();
        $mockRepo->expects($this->once())
            ->method('deleteBy')
            ->willReturn(true);

        $service = $this->getMockBuilder(CommonLangModService::class)
            ->onlyMethods(['getLangMapRepository'])
            ->getMock();
        $service->expects($this->once())
            ->method('getLangMapRepository')
            ->with('en-CN')
            ->willReturn($mockRepo);

        $service->deleteLang($companyId, $tableName, $dataId, $module);
    }

    /**
     * getDefaultLang 返回配置的默认语言（新 config 有 list/default 时取 default）。
     * 计划：default-lang-config TODO-2 / TC4 相关
     */
    public function testGetDefaultLangReturnsConfiguredDefault(): void
    {
        $service = new CommonLangModService();
        $this->assertSame(config('langue.default'), $service->getDefaultLang());
    }

    /**
     * 旧格式 config（无 list 键、纯数组）时，getDefaultLang 为首元素。
     * 计划：default-lang-config TC4
     */
    public function testGetDefaultLangWithLegacyConfigUsesFirstElement(): void
    {
        $original = config('langue');
        $legacy = ['en-CN', 'zh-CN', 'ar-SA'];
        config(['langue' => $legacy]);
        try {
            $service = new CommonLangModService();
            $this->assertSame('en-CN', $service->getDefaultLang());
        } finally {
            config(['langue' => $original]);
        }
    }

    /**
     * 新格式 config（有 list/default）时，totalLang 为 list、getDefaultLang 为 default。
     * 计划：default-lang-config 1.2
     */
    public function testNewConfigUsesListAndDefault(): void
    {
        $service = new CommonLangModService();
        $this->assertSame(config('langue.list'), $service->totalLang);
        $this->assertSame(config('langue.default'), $service->getDefaultLang());
    }

    /**
     * isDefaultLang 不传参时用 getLang() 与 getDefaultLang() 严格比较。
     * 计划：default-lang-config 2.5
     */
    public function testIsDefaultLangWithoutParamComparesGetLangWithGetDefaultLang(): void
    {
        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'zh-CN']));
        $service = new CommonLangModService();
        $this->assertTrue($service->isDefaultLang());

        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'en-CN']));
        $service2 = new CommonLangModService();
        $this->assertFalse($service2->isDefaultLang());
    }

    /**
     * isDefaultLang 传参时与 getDefaultLang() 严格比较。
     */
    public function testIsDefaultLangWithParamComparesToDefault(): void
    {
        $service = new CommonLangModService();
        $this->assertTrue($service->isDefaultLang('zh-CN'));
        $this->assertFalse($service->isDefaultLang('en-CN'));
    }

    /**
     * 从 data 中按 langField 主键名剔除，返回新数组；主键名为 explode('|',$v)[0]；原数组不变。
     * 计划：default-lang-config 2.2 / 2.5
     */
    public function testStripLangFieldsFromDataRemovesLangKeysAndReturnsCopy(): void
    {
        $service = new CommonLangModService();
        $data = ['id' => 1, 'content' => 'x', 'title' => 'y'];
        $langField = ['content|json', 'title'];
        $result = $service->stripLangFieldsFromData($data, $langField);
        $this->assertSame(['id' => 1], $result);
        $this->assertSame(['id' => 1, 'content' => 'x', 'title' => 'y'], $data);
    }
}
