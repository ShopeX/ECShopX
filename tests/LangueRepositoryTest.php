<?php

use CompanysBundle\Repositories\LangueRepository;
use CompanysBundle\Services\CommonLangModService;

/**
 * TC6（计划 default-lang-config）：LangueRepository 路径
 * 非默认语言下 createLangue/updateOneByLangue 时，原始表写剔除多语言字段的副本，语言表仍用原 $data；createLangue 的 saveLang data_id 为主键值。
 */
class LangueRepositoryTest extends TestCase
{
    /**
     * TC6 createLangue：非默认语言时 setColumnNamesData 收到的是剔除多语言字段后的副本；saveLang 的 data_id 为实体主键值。
     */
    public function testCreateLangueWhenNotDefaultLangUsesStrippedDataAndPrimaryKeyValue(): void
    {
        $setColumnNamesDataReceived = null;
        $saveLangDataId = null;

        $entity = new \stdClass();
        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getEntity', 'setColumnNamesData', 'getEntityManager', 'getColumnNamesData'])
            ->getMock();
        $mockRepo->primaryKey = 'id';
        $mockRepo->table = 'test_table';
        $mockRepo->module = 'test_module';
        $mockRepo->langField = ['content|json', 'title|json'];

        $mockRepo->method('getEntity')->willReturn($entity);
        $mockRepo->method('setColumnNamesData')->willReturnCallback(
            function ($e, $data) use (&$setColumnNamesDataReceived) {
                $setColumnNamesDataReceived = $data;
                return $e;
            }
        );
        $em = $this->getMockBuilder(\stdClass::class)->addMethods(['persist', 'flush'])->getMock();
        $em->method('persist')->willReturn(null);
        $em->method('flush')->willReturn(null);
        $mockRepo->method('getEntityManager')->willReturn($em);
        $mockRepo->method('getColumnNamesData')->willReturn(['id' => 1, 'company_id' => 1]);

        $mockService = $this->getMockBuilder(CommonLangModService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDefaultLang', 'stripLangFieldsFromData', 'getLangData', 'saveLang'])
            ->getMock();
        $mockService->method('isDefaultLang')->willReturn(false);
        $mockService->method('stripLangFieldsFromData')->willReturnCallback(
            function (array $data, array $langField) {
                $copy = $data;
                foreach ($langField as $v) {
                    $key = explode('|', $v)[0] ?? '';
                    if ($key !== '') {
                        unset($copy[$key]);
                    }
                }
                return $copy;
            }
        );
        $mockService->method('getLangData')->willReturn(['langBag' => []]);
        $mockService->method('saveLang')->willReturnCallback(
            function ($companyId, $langBag, $table, $dataId, $module) use (&$saveLangDataId) {
                $saveLangDataId = $dataId;
            }
        );

        $sut = new LangueRepository($mockRepo, $mockService);
        $data = ['company_id' => 1, 'content' => 'en content', 'title' => 'en title'];
        $sut->createLangue($data);

        $this->assertNotNull($setColumnNamesDataReceived, 'setColumnNamesData 应被调用');
        $this->assertArrayHasKey('company_id', $setColumnNamesDataReceived);
        $this->assertArrayNotHasKey('content', $setColumnNamesDataReceived, '非默认语言时主表不应写入多语言字段 content');
        $this->assertArrayNotHasKey('title', $setColumnNamesDataReceived, '非默认语言时主表不应写入多语言字段 title');
        $this->assertSame(1, $saveLangDataId, 'saveLang 的 data_id 应为实体主键值 1，而非主键名');
        $this->assertArrayHasKey('content', $data, '调用方 $data 未被篡改');
        $this->assertSame('en content', $data['content']);
    }

    /**
     * TC6 updateOneByLangue：非默认语言时 setColumnNamesData 收到剔除多语言字段后的副本。
     */
    public function testUpdateOneByLangueWhenNotDefaultLangPassesStrippedDataToSetColumnNamesData(): void
    {
        $setColumnNamesDataReceived = null;
        $entity = new \stdClass();
        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy', 'setColumnNamesData', 'getEntityManager', 'getColumnNamesData'])
            ->getMock();
        $mockRepo->primaryKey = 'id';
        $mockRepo->table = 'test_table';
        $mockRepo->module = 'test_module';
        $mockRepo->langField = ['content|json'];

        $mockRepo->method('findOneBy')->willReturn($entity);
        $mockRepo->method('setColumnNamesData')->willReturnCallback(
            function ($e, $data) use (&$setColumnNamesDataReceived) {
                $setColumnNamesDataReceived = $data;
                return $e;
            }
        );
        $em = $this->getMockBuilder(\stdClass::class)->addMethods(['persist', 'flush'])->getMock();
        $em->method('persist')->willReturn(null);
        $em->method('flush')->willReturn(null);
        $mockRepo->method('getEntityManager')->willReturn($em);
        $mockRepo->method('getColumnNamesData')->willReturn(['id' => 1, 'company_id' => 1]);

        $mockService = $this->getMockBuilder(CommonLangModService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDefaultLang', 'stripLangFieldsFromData', 'getLangData', 'updateLangData'])
            ->getMock();
        $mockService->method('isDefaultLang')->willReturn(false);
        $mockService->method('stripLangFieldsFromData')->willReturn(['company_id' => 1]);
        $mockService->method('getLangData')->willReturn(['langBag' => []]);
        $mockService->method('updateLangData')->willReturn(null);

        $sut = new LangueRepository($mockRepo, $mockService);
        $data = ['company_id' => 1, 'content' => 'en content'];
        $sut->updateOneByLangue(['id' => 1], $data);

        $this->assertNotNull($setColumnNamesDataReceived);
        $this->assertArrayNotHasKey('content', $setColumnNamesDataReceived, '非默认语言时主表不应写入多语言字段 content');
    }

    /**
     * TC6 updateByLangue：非默认语言时 setColumnNamesData 收到剔除多语言字段后的副本。
     */
    public function testUpdateByLangueWhenNotDefaultLangPassesStrippedDataToSetColumnNamesData(): void
    {
        $setColumnNamesDataReceived = null;
        $entity = new \stdClass();
        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findBy', 'setColumnNamesData', 'getEntityManager', 'getColumnNamesData'])
            ->getMock();
        $mockRepo->primaryKey = 'id';
        $mockRepo->table = 'test_table';
        $mockRepo->module = 'test_module';
        $mockRepo->langField = ['content|json'];

        $mockRepo->method('findBy')->willReturn([$entity]);
        $mockRepo->method('setColumnNamesData')->willReturnCallback(
            function ($e, $data) use (&$setColumnNamesDataReceived) {
                $setColumnNamesDataReceived = $data;
                return $e;
            }
        );
        $em = $this->getMockBuilder(\stdClass::class)->addMethods(['persist', 'flush'])->getMock();
        $em->method('persist')->willReturn(null);
        $em->method('flush')->willReturn(null);
        $mockRepo->method('getEntityManager')->willReturn($em);
        $mockRepo->method('getColumnNamesData')->willReturn(['id' => 1, 'company_id' => 1]);

        $mockService = $this->getMockBuilder(CommonLangModService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDefaultLang', 'stripLangFieldsFromData', 'getLangData', 'updateLangData'])
            ->getMock();
        $mockService->method('isDefaultLang')->willReturn(false);
        $mockService->method('stripLangFieldsFromData')->willReturn(['company_id' => 1]);
        $mockService->method('getLangData')->willReturn(['langBag' => []]);
        $mockService->method('updateLangData')->willReturn(null);

        $sut = new LangueRepository($mockRepo, $mockService);
        $data = ['company_id' => 1, 'content' => 'en content'];
        $sut->updateByLangue(['id' => 1], $data);

        $this->assertNotNull($setColumnNamesDataReceived);
        $this->assertArrayNotHasKey('content', $setColumnNamesDataReceived, '非默认语言时主表不应写入多语言字段 content');
    }
}
