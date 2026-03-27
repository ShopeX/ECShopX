<?php

use CompanysBundle\Services\RepositoryLangInterceptor;

/**
 * 单元测试：RepositoryLangInterceptor 分页与执行顺序（T2 updateBy >1 页、T5 deleteBy >1 页）
 * 计划：.tasks/plans/repository-lang-interceptor-batch-fix.md
 */
class RepositoryLangInterceptorTest extends TestCase
{
    /**
     * 生成模拟的 list 项（含 company_id、主键）
     */
    private function makeListItems(string $primaryKey, int $count, int $startId = 1): array
    {
        $list = [];
        for ($i = 0; $i < $count; $i++) {
            $list[] = [
                'company_id' => 1,
                $primaryKey => $startId + $i,
            ];
        }
        return $list;
    }

    /**
     * T1: updateBy ≤1 页 — Mock lists 返回 total_count=N（N≤pageSize）、list 含 N 条；断言 updateLangData 被调用 N 次。
     */
    public function testUpdateBySinglePageCallsUpdateLangDataNTimes(): void
    {
        $primaryKey = 'id';
        $callLog = [];
        $N = 10;
        $singlePage = $this->makeListItems($primaryKey, $N, 1);

        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists', 'updateBy'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = [];

        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnOnConsecutiveCalls(
                ['list' => $singlePage, 'total_count' => $N],
                ['list' => [], 'total_count' => $N]
            );
        $target->expects($this->once())
            ->method('updateBy')
            ->willReturnCallback(function () use (&$callLog) {
                $callLog[] = 'targetUpdateBy';
            });

        $spy = $this->createCommonLangSpy($callLog);

        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['company_id' => 1];
        $data = ['name' => 'x'];
        $interceptor->updateBy($filter, $data);

        $updateLangDataCount = $this->countInLog($callLog, 'updateLangData');
        $this->assertSame($N, $updateLangDataCount, 'updateLangData 应被调用 N 次（≤1 页时 N 条）');
    }

    /**
     * T2: updateBy 多页 — 第1页100条、第2页50条、第3页0条，共150条。
     * 断言：updateLangData 被调用 150 次；先 target->updateBy 再 updateLangData。
     */
    public function testUpdateByMultiplePagesCallsUpdateLangDataForAllRecordsAndTargetBeforeMultilang(): void
    {
        $primaryKey = 'id';
        $callLog = [];
        $page1 = $this->makeListItems($primaryKey, 100, 1);
        $page2 = $this->makeListItems($primaryKey, 50, 101);
        $page3 = [];

        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists', 'updateBy'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = [];

        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnOnConsecutiveCalls(
                ['list' => $page1, 'total_count' => 150],
                ['list' => $page2, 'total_count' => 150],
                ['list' => $page3, 'total_count' => 150]
            );
        $target->expects($this->once())
            ->method('updateBy')
            ->willReturnCallback(function () use (&$callLog) {
                $callLog[] = 'targetUpdateBy';
            });

        $spy = $this->createCommonLangSpy($callLog);

        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['company_id' => 1];
        $data = ['name' => 'x'];
        $interceptor->updateBy($filter, $data);

        $updateLangDataCount = $this->countInLog($callLog, 'updateLangData');
        $firstTarget = $this->firstIndexInLog($callLog, 'targetUpdateBy');
        $firstMultilang = $this->firstIndexInLog($callLog, 'updateLangData');

        $this->assertSame(150, $updateLangDataCount, 'updateLangData 应被调用 150 次（多页总条数）');
        $this->assertLessThan($firstMultilang, $firstTarget, '应先执行 target->updateBy 再执行 updateLangData');
    }

    /**
     * T3: updateBy 参数 — Mock target->lists：断言调用时第二个参数不是 array（即未传入 data），仅首参为 filter。
     */
    public function testUpdateByListsCalledWithFilterOnlyNotData(): void
    {
        $primaryKey = 'id';
        $callLog = [];
        $singlePage = $this->makeListItems($primaryKey, 1, 1);

        $listsCalls = [];
        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists', 'updateBy'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = [];

        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnCallback(function ($firstArg, $secondArg = null) use ($singlePage, &$listsCalls) {
                $listsCalls[] = ['first' => $firstArg, 'second' => $secondArg];
                if (count($listsCalls) === 1) {
                    return ['list' => $singlePage, 'total_count' => 1];
                }
                return ['list' => [], 'total_count' => 1];
            });
        $target->expects($this->once())
            ->method('updateBy')
            ->willReturn(true);

        $spy = $this->createCommonLangSpy($callLog);

        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['company_id' => 1];
        $data = ['name' => 'x'];
        $interceptor->updateBy($filter, $data);

        $this->assertNotEmpty($listsCalls, 'lists 应被调用');
        foreach ($listsCalls as $call) {
            $this->assertSame($filter, $call['first'], 'lists 首参应为 filter');
            $this->assertFalse(is_array($call['second']), 'lists 第二参数不应为 array（即未传入 data）');
        }
    }

    /**
     * T5: deleteBy 多页 — 分页共 200 条。
     * 断言：deleteLang 被调用 200 次；先 deleteLang 再 target->deleteBy。
     */
    public function testDeleteByMultiplePagesCallsDeleteLangForAllRecordsAndMultilangBeforeTarget(): void
    {
        $primaryKey = 'id';
        $callLog = [];
        $page1 = $this->makeListItems($primaryKey, 100, 1);
        $page2 = $this->makeListItems($primaryKey, 100, 101);
        $page3 = [];

        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists', 'deleteBy'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = [];

        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnOnConsecutiveCalls(
                ['list' => $page1, 'total_count' => 200],
                ['list' => $page2, 'total_count' => 200],
                ['list' => $page3, 'total_count' => 200]
            );
        $target->expects($this->once())
            ->method('deleteBy')
            ->willReturnCallback(function () use (&$callLog) {
                $callLog[] = 'targetDeleteBy';
            });

        $spy = $this->createCommonLangSpy($callLog);

        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['company_id' => 1];
        $interceptor->deleteBy($filter);

        $deleteLangCount = $this->countInLog($callLog, 'deleteLang');
        $lastDeleteLang = $this->lastIndexInLog($callLog, 'deleteLang');
        $deleteByIndex = $this->firstIndexInLog($callLog, 'targetDeleteBy');

        $this->assertSame(200, $deleteLangCount, 'deleteLang 应被调用 200 次（多页总条数）');
        $this->assertGreaterThan($lastDeleteLang, $deleteByIndex, '应先执行完所有 deleteLang 再执行 target->deleteBy');
    }

    /**
     * T4: deleteBy ≤1 页 — Mock lists 返回 1 页；断言 deleteLang 被调用次数 = list 条数。
     */
    public function testDeleteBySinglePageCallsDeleteLangEqualToListCount(): void
    {
        $primaryKey = 'id';
        $callLog = [];
        $listCount = 5;
        $singlePage = $this->makeListItems($primaryKey, $listCount, 1);

        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists', 'deleteBy'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = [];

        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnOnConsecutiveCalls(
                ['list' => $singlePage, 'total_count' => $listCount],
                ['list' => [], 'total_count' => $listCount]
            );
        $target->expects($this->once())
            ->method('deleteBy')
            ->willReturn(true);

        $spy = $this->createCommonLangSpy($callLog);

        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['company_id' => 1];
        $interceptor->deleteBy($filter);

        $deleteLangCount = $this->countInLog($callLog, 'deleteLang');
        $this->assertSame($listCount, $deleteLangCount, 'deleteLang 被调用次数应等于 list 条数');
    }

    /**
     * T6: delete 与 deleteBy 一致 — 调用 delete($filter)，mock lists 多页；断言 deleteLang 调用次数 = 总条数，且先多语言再 target->delete。
     */
    public function testDeleteMultiplePagesCallsDeleteLangForAllThenTargetDelete(): void
    {
        $primaryKey = 'id';
        $callLog = [];
        $page1 = $this->makeListItems($primaryKey, 80, 1);
        $page2 = $this->makeListItems($primaryKey, 30, 81);
        $page3 = [];
        $totalRows = 110;

        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists', 'delete'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = [];

        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnOnConsecutiveCalls(
                ['list' => $page1, 'total_count' => $totalRows],
                ['list' => $page2, 'total_count' => $totalRows],
                ['list' => $page3, 'total_count' => $totalRows]
            );
        $target->expects($this->once())
            ->method('delete')
            ->willReturnCallback(function () use (&$callLog) {
                $callLog[] = 'targetDelete';
            });

        $spy = $this->createCommonLangSpy($callLog);

        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['company_id' => 1];
        $interceptor->delete($filter);

        $deleteLangCount = $this->countInLog($callLog, 'deleteLang');
        $lastDeleteLang = $this->lastIndexInLog($callLog, 'deleteLang');
        $deleteIndex = $this->firstIndexInLog($callLog, 'targetDelete');

        $this->assertSame($totalRows, $deleteLangCount, 'deleteLang 应被调用总条数次（与 deleteBy 一致）');
        $this->assertGreaterThan($lastDeleteLang, $deleteIndex, '应先执行完所有 deleteLang 再执行 target->delete');
    }

    /**
     * @param array $options 可选 'isDefaultLang' => bool (默认 true), 'langField' => array
     */
    private function createCommonLangSpy(array &$callLog, array $options = []): object
    {
        $isDefaultLang = $options['isDefaultLang'] ?? true;
        return new class($callLog, $isDefaultLang) {
            private $callLog;
            private $isDefaultLang;

            public function __construct(array &$callLog, bool $isDefaultLang = true)
            {
                $this->callLog = &$callLog;
                $this->isDefaultLang = $isDefaultLang;
            }

            public function getParamsLang($repository, $data)
            {
                return $data;
            }

            public function getLangData($data, array $fields)
            {
                return ['data' => $data, 'langBag' => []];
            }

            public function isDefaultLang(?string $lang = null): bool
            {
                return $this->isDefaultLang;
            }

            public function stripLangFieldsFromData(array $data, array $langField): array
            {
                $copy = $data;
                foreach ($langField as $v) {
                    $key = explode('|', $v)[0] ?? '';
                    if ($key !== '') {
                        unset($copy[$key]);
                    }
                }
                return $copy;
            }

            public function saveLang(int $companyId, array $langBag, string $table, int $id, string $module, $langue = '')
            {
                $this->callLog[] = 'saveLang';
            }

            public function updateLangData(int $companyId, array $langBag, string $tableName, int $dataId, string $module, $langue = '')
            {
                $this->callLog[] = 'updateLangData';
            }

            public function deleteLang(int $companyId, string $tableName, int $dataId, string $module)
            {
                $this->callLog[] = 'deleteLang';
            }
        };
    }

    private function injectCommonLangService(RepositoryLangInterceptor $interceptor, object $service): void
    {
        $ref = new \ReflectionClass($interceptor);
        $prop = $ref->getProperty('commonLangModService');
        $prop->setAccessible(true);
        $prop->setValue($interceptor, $service);
    }

    private function countInLog(array $callLog, string $tag): int
    {
        $n = 0;
        foreach ($callLog as $entry) {
            if ($entry === $tag) {
                $n++;
            }
        }
        return $n;
    }

    private function firstIndexInLog(array $callLog, string $tag): ?int
    {
        foreach ($callLog as $i => $entry) {
            if ($entry === $tag) {
                return $i;
            }
        }
        return null;
    }

    private function lastIndexInLog(array $callLog, string $tag): ?int
    {
        $last = null;
        foreach ($callLog as $i => $entry) {
            if ($entry === $tag) {
                $last = $i;
            }
        }
        return $last;
    }

    /**
     * processByFilterInPages：仅用 filter 分页循环，每页 list 交给回调；list 为空即停止。
     * #given mock target->lists 第1页100条、第2页50条、第3页空
     * #when 通过反射调用 processByFilterInPages($filter, $processPageList)
     * #then 回调被调用 2 次，收到的 list 长度分别为 100、50；lists 调用参数为 (filter,'*',page,500)
     */
    public function testProcessByFilterInPagesIteratesUntilListEmptyAndCallsCallbackPerPage(): void
    {
        $primaryKey = 'id';
        $page1 = $this->makeListItems($primaryKey, 100, 1);
        $page2 = $this->makeListItems($primaryKey, 50, 101);
        $page3 = [];

        $listsCalls = [];
        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = [];

        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnCallback(function ($filter, $cols, $page, $pageSize) use ($page1, $page2, $page3, &$listsCalls) {
                $listsCalls[] = ['filter' => $filter, 'cols' => $cols, 'page' => $page, 'pageSize' => $pageSize];
                if ($page === 1) {
                    return ['list' => $page1, 'total_count' => 150];
                }
                if ($page === 2) {
                    return ['list' => $page2, 'total_count' => 150];
                }
                return ['list' => $page3, 'total_count' => 150];
            });

        $interceptor = new RepositoryLangInterceptor($target);
        $receivedLists = [];
        $processPageList = function (array $list) use (&$receivedLists) {
            $receivedLists[] = $list;
        };

        $filter = ['company_id' => 1];
        $ref = new \ReflectionClass($interceptor);
        $method = $ref->getMethod('processByFilterInPages');
        $method->setAccessible(true);
        $method->invoke($interceptor, $filter, $processPageList);

        $this->assertCount(2, $receivedLists, '回调应被调用 2 次（两页有数据）');
        $this->assertCount(100, $receivedLists[0], '第 1 页 list 长度 100');
        $this->assertCount(50, $receivedLists[1], '第 2 页 list 长度 50');
        $this->assertCount(3, $listsCalls, 'lists 应被调用 3 次（第 3 次返回空结束）');
        $this->assertSame($filter, $listsCalls[0]['filter']);
        $this->assertSame('*', $listsCalls[0]['cols']);
        $this->assertSame(500, $listsCalls[0]['pageSize']);
        $this->assertSame(1, $listsCalls[0]['page']);
        $this->assertSame(2, $listsCalls[1]['page']);
        $this->assertSame(3, $listsCalls[2]['page']);
    }

    /**
     * TC1（计划 default-lang-config）：默认语言 create 时，target->create 收到原样 params（含多语言字段）。
     */
    public function testCreateWhenDefaultLangPassesParamsUnchangedToTarget(): void
    {
        $primaryKey = 'id';
        $langField = ['content|json'];
        $createReceived = null;
        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = $langField;
        $target->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (...$args) use (&$createReceived, $primaryKey) {
                $createReceived = $args;
                return [$primaryKey => 1, 'company_id' => 1];
            });

        $callLog = [];
        $spy = $this->createCommonLangSpy($callLog, ['isDefaultLang' => true]);
        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $data = ['name' => 'n1', 'content' => 'zh value'];
        $interceptor->create($data);

        $this->assertNotNull($createReceived);
        $this->assertCount(1, $createReceived);
        $passedData = $createReceived[0];
        $this->assertArrayHasKey('content', $passedData, '默认语言时应原样传参，含多语言字段');
        $this->assertSame('zh value', $passedData['content']);
    }

    /**
     * TC2（计划 default-lang-config）：非默认语言 create 时，target->create 收到的是剔除多语言字段后的 data，原始表不写多语言列。
     */
    public function testCreateWhenNotDefaultLangPassesStrippedDataToTarget(): void
    {
        $primaryKey = 'id';
        $langField = ['content|json'];
        $createReceived = null;
        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = $langField;
        $target->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (...$args) use (&$createReceived, $primaryKey) {
                $createReceived = $args;
                return [$primaryKey => 1, 'company_id' => 1];
            });

        $callLog = [];
        $spy = $this->createCommonLangSpy($callLog, ['isDefaultLang' => false]);
        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $data = ['name' => 'n1', 'content' => 'lang value'];
        $interceptor->create($data);

        $this->assertNotNull($createReceived, 'target->create 应被调用');
        $this->assertCount(1, $createReceived, 'create 单参数即 data');
        $passedData = $createReceived[0];
        $this->assertIsArray($passedData);
        $this->assertArrayHasKey('name', $passedData);
        $this->assertSame('n1', $passedData['name']);
        $this->assertArrayNotHasKey('content', $passedData, '非默认语言时不应向原始表写入多语言字段 content');
    }

    /**
     * TC2 updateOneBy：非默认语言时 target->updateOneBy 收到 (filter, 剔除多语言字段后的 data)。
     */
    public function testUpdateOneByWhenNotDefaultLangPassesStrippedDataToTarget(): void
    {
        $primaryKey = 'id';
        $langField = ['content|json'];
        $updateOneByReceived = null;
        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['updateOneBy'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = $langField;
        $target->expects($this->once())
            ->method('updateOneBy')
            ->willReturnCallback(function (...$args) use (&$updateOneByReceived, $primaryKey) {
                $updateOneByReceived = $args;
                return [$primaryKey => 1, 'company_id' => 1];
            });

        $callLog = [];
        $spy = $this->createCommonLangSpy($callLog, ['isDefaultLang' => false]);
        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['id' => 1];
        $data = ['name' => 'u1', 'content' => 'lang value'];
        $interceptor->updateOneBy($filter, $data);

        $this->assertNotNull($updateOneByReceived);
        $this->assertCount(2, $updateOneByReceived, '约定 (filter, data)');
        $this->assertSame($filter, $updateOneByReceived[0]);
        $passedData = $updateOneByReceived[1];
        $this->assertArrayHasKey('name', $passedData);
        $this->assertSame('u1', $passedData['name']);
        $this->assertArrayNotHasKey('content', $passedData, '非默认语言时不应向原始表写入多语言字段');
    }

    /**
     * TC5（计划 default-lang-config）：非默认语言 updateBy 时，target->updateBy 收到 (filter, 剔除多语言字段后的 data)。
     */
    public function testUpdateByWhenNotDefaultLangPassesStrippedDataToTarget(): void
    {
        $primaryKey = 'id';
        $langField = ['content|json'];
        $updateByReceived = null;
        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['updateBy', 'lists'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = $langField;
        $target->expects($this->once())
            ->method('updateBy')
            ->willReturnCallback(function ($filter, ...$args) use (&$updateByReceived) {
                $updateByReceived = [$filter, $args];
                return 1;
            });
        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnOnConsecutiveCalls(
                ['list' => [], 'total_count' => 0]
            );

        $callLog = [];
        $spy = $this->createCommonLangSpy($callLog, ['isDefaultLang' => false]);
        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['company_id' => 1];
        $data = ['name' => 'u1', 'content' => 'lang value'];
        $interceptor->updateBy($filter, $data);

        $this->assertNotNull($updateByReceived);
        $this->assertCount(2, $updateByReceived);
        $this->assertSame($filter, $updateByReceived[0]);
        $params = $updateByReceived[1];
        $this->assertCount(1, $params, '约定 params[0] 为 data');
        $passedData = $params[0];
        $this->assertArrayHasKey('name', $passedData);
        $this->assertSame('u1', $passedData['name']);
        $this->assertArrayNotHasKey('content', $passedData, '非默认语言时不应向原始表写入多语言字段');
    }

    /**
     * TC7 updateOneBy：调用方传入的 data 在 updateOneBy 后未被篡改。
     */
    public function testUpdateOneByWhenNotDefaultLangDoesNotMutateCallerData(): void
    {
        $primaryKey = 'id';
        $langField = ['content|json'];
        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['updateOneBy'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = $langField;
        $target->expects($this->once())->method('updateOneBy')->willReturn([$primaryKey => 1, 'company_id' => 1]);

        $callLog = [];
        $spy = $this->createCommonLangSpy($callLog, ['isDefaultLang' => false]);
        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['id' => 1];
        $data = ['name' => 'u1', 'content' => 'lang value'];
        $interceptor->updateOneBy($filter, $data);

        $this->assertArrayHasKey('content', $data);
        $this->assertSame('lang value', $data['content'], '调用方 data 的 content 未被篡改');
    }

    /**
     * TC7（计划 default-lang-config）：调用方传入的 data 在 create 后未被篡改，Interceptor 只对副本剔除。
     */
    public function testCreateWhenNotDefaultLangDoesNotMutateCallerData(): void
    {
        $primaryKey = 'id';
        $langField = ['content|json'];
        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = $langField;
        $target->expects($this->once())->method('create')->willReturn([$primaryKey => 1, 'company_id' => 1]);

        $callLog = [];
        $spy = $this->createCommonLangSpy($callLog, ['isDefaultLang' => false]);
        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $data = ['name' => 'n1', 'content' => 'lang value'];
        $interceptor->create($data);

        $this->assertArrayHasKey('content', $data, '调用方 data 应仍含 content');
        $this->assertSame('lang value', $data['content'], '调用方 data 的 content 未被篡改');
    }

    /**
     * T7: 分页循环一致性 — 仅用分页循环时，多页 mock 下断言多语言同步条数正确。
     */
    public function testPaginationLoopSyncCountConsistency(): void
    {
        $primaryKey = 'id';
        $callLog = [];
        $page1 = $this->makeListItems($primaryKey, 40, 1);
        $page2 = $this->makeListItems($primaryKey, 30, 41);
        $page3 = $this->makeListItems($primaryKey, 20, 71);
        $page4 = [];
        $totalRows = 90;

        $target = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists', 'deleteBy'])
            ->getMock();
        $target->table = 'test_table';
        $target->module = 'test_module';
        $target->primaryKey = $primaryKey;
        $target->langField = [];

        $target->expects($this->atLeastOnce())
            ->method('lists')
            ->willReturnOnConsecutiveCalls(
                ['list' => $page1, 'total_count' => $totalRows],
                ['list' => $page2, 'total_count' => $totalRows],
                ['list' => $page3, 'total_count' => $totalRows],
                ['list' => $page4, 'total_count' => $totalRows]
            );
        $target->expects($this->once())
            ->method('deleteBy')
            ->willReturn(true);

        $spy = $this->createCommonLangSpy($callLog);

        $interceptor = new RepositoryLangInterceptor($target);
        $this->injectCommonLangService($interceptor, $spy);

        $filter = ['company_id' => 1];
        $interceptor->deleteBy($filter);

        $deleteLangCount = $this->countInLog($callLog, 'deleteLang');
        $this->assertSame($totalRows, $deleteLangCount, '分页循环下多语言同步条数应等于总条数');
    }
}
