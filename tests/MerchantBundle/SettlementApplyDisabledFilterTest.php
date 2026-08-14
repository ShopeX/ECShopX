<?php

declare(strict_types=1);

namespace Tests\MerchantBundle;

use Dingo\Api\Http\Response\Factory;
use Illuminate\Http\Request;
use MerchantBundle\Entities\MerchantSettlementApply;
use MerchantBundle\Http\Api\V1\Action\MerchantSettlementApply as MerchantSettlementApplyAction;
use MerchantBundle\Services\MerchantSettlementApplyService;
use Mockery;

/**
 * 商户入驻申请 — 列表 disabled 过滤 + 详情类型兜底 — TC1–TC5（RED/GREEN）
 * 计划：.tasks/plans/settlement-apply-disabled-filter.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SettlementApplyDisabledFilterTest extends \TestCase
{
    /**
     * 最小 Lumen 容器，避免 bootstrap/app.php 触发 Doctrine DB 连接。
     */
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();
        $app->instance('path.lang', $app->basePath('resources/lang'));
        $app->register(\Illuminate\Translation\TranslationServiceProvider::class);
        $app->register(\Illuminate\Validation\ValidationServiceProvider::class);

        return $app;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC1 / AC1：getList filter 含 company_id + disabled=0，不含 source。
     * #given 平台登录 company_id=1
     * #when getList
     * #then lists filter 含 company_id、disabled=0，无 source
     */
    public function testGetListFilterIncludesDisabledZeroWithoutSource(): void
    {
        $this->bindAuth(1);
        $this->bindResponseFactory();

        $capturedFilter = null;
        $serviceMock = Mockery::mock('overload:MerchantBundle\Services\MerchantSettlementApplyService');
        $serviceMock->shouldReceive('lists')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = Request::create('/merchant/settlement/apply/list', 'GET', [
            'page' => 1,
            'page_size' => 10,
        ]);

        $controller = new MerchantSettlementApplyAction();
        $controller->getList($request);

        $this->assertIsArray($capturedFilter);
        $this->assertSame(1, $capturedFilter['company_id']);
        $this->assertSame(0, $capturedFilter['disabled']);
        $this->assertArrayNotHasKey('source', $capturedFilter);
    }

    /**
     * TC2 / AC2：带 audit_status 等 query 时原有条件仍在，且始终含 disabled=0。
     * #given 平台登录，传 audit_status、merchant_name 等筛选项
     * #when getList
     * #then lists filter 保留筛选项且含 disabled=0
     */
    public function testGetListWithAuditStatusKeepsFiltersAndDisabledZero(): void
    {
        $this->bindAuth(1);
        $this->bindResponseFactory();

        $capturedFilter = null;
        $serviceMock = Mockery::mock('overload:MerchantBundle\Services\MerchantSettlementApplyService');
        $serviceMock->shouldReceive('lists')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = Request::create('/merchant/settlement/apply/list', 'GET', [
            'page' => 1,
            'page_size' => 10,
            'audit_status' => '1',
            'merchant_name' => '测试商户',
            'province' => '上海市',
            'settled_type' => 'enterprise',
        ]);

        $controller = new MerchantSettlementApplyAction();
        $controller->getList($request);

        $this->assertIsArray($capturedFilter);
        $this->assertSame(1, $capturedFilter['company_id']);
        $this->assertSame(0, $capturedFilter['disabled']);
        $this->assertSame('1', $capturedFilter['audit_status']);
        $this->assertSame('测试商户', $capturedFilter['merchant_name|contains']);
        $this->assertSame('上海市', $capturedFilter['province']);
        $this->assertSame('enterprise', $capturedFilter['settled_type']);
        $this->assertArrayNotHasKey('source', $capturedFilter);
    }

    /**
     * TC3 / AC3：merchant_type_id=0 时不抛异常，返回空类型名，不调用 getTypeNameById。
     * #given 入驻申请 merchant_type_id=0
     * #when getSettlementApplyDetail
     * #then 三类型名字段为空串，getTypeNameById 未调用
     */
    public function testDetailWithMerchantTypeIdZeroReturnsEmptyTypeNamesWithoutCallingGetTypeNameById(): void
    {
        $settingMock = Mockery::mock('overload:MerchantBundle\Services\MerchantSettingService');
        $settingMock->shouldReceive('getTypeNameById')->never();

        $applyInfo = [
            'id' => '100',
            'company_id' => 1,
            'merchant_type_id' => 0,
            'mobile' => '13000000000',
        ];
        $service = $this->createServiceWithApplyInfo($applyInfo);

        $result = $service->getSettlementApplyDetail('100');

        $this->assertSame('', $result['merchant_type_parent_id']);
        $this->assertSame('', $result['merchant_type_parent_name']);
        $this->assertSame('', $result['merchant_type_name']);
    }

    /**
     * TC4 / AC4：merchant_type_id 为 '0' / null / '' 时走空类型分支。
     *
     * @dataProvider invalidMerchantTypeIdProvider
     * #given 入驻申请 merchant_type_id 无效
     * #when getSettlementApplyDetail
     * #then 三类型名字段为空串，getTypeNameById 未调用
     */
    public function testDetailWithInvalidMerchantTypeIdUsesEmptyTypeBranch($merchantTypeId): void
    {
        $settingMock = Mockery::mock('overload:MerchantBundle\Services\MerchantSettingService');
        $settingMock->shouldReceive('getTypeNameById')->never();

        $applyInfo = [
            'id' => '100',
            'company_id' => 1,
            'merchant_type_id' => $merchantTypeId,
            'mobile' => '13000000000',
        ];
        $service = $this->createServiceWithApplyInfo($applyInfo);

        $result = $service->getSettlementApplyDetail('100');

        $this->assertSame('', $result['merchant_type_parent_id']);
        $this->assertSame('', $result['merchant_type_parent_name']);
        $this->assertSame('', $result['merchant_type_name']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidMerchantTypeIdProvider(): array
    {
        return [
            'string zero' => ['0'],
            'null' => [null],
            'empty string' => [''],
        ];
    }

    /**
     * TC5 / AC5：有效 merchant_type_id>0 仍调用 getTypeNameById 并合并返回。
     * #given 入驻申请 merchant_type_id=5
     * #when getSettlementApplyDetail
     * #then 合并类型名字段
     */
    public function testDetailWithValidMerchantTypeIdMergesTypeNames(): void
    {
        $typeNames = [
            'merchant_type_parent_id' => 2,
            'merchant_type_parent_name' => '一级类型',
            'merchant_type_name' => '二级类型',
        ];
        $settingMock = Mockery::mock('overload:MerchantBundle\Services\MerchantSettingService');
        $settingMock->shouldReceive('getTypeNameById')
            ->once()
            ->with(1, 5)
            ->andReturn($typeNames);

        $applyInfo = [
            'id' => '100',
            'company_id' => 1,
            'merchant_type_id' => 5,
            'mobile' => '13000000000',
        ];
        $service = $this->createServiceWithApplyInfo($applyInfo);

        $result = $service->getSettlementApplyDetail('100');

        $this->assertSame(2, $result['merchant_type_parent_id']);
        $this->assertSame('一级类型', $result['merchant_type_parent_name']);
        $this->assertSame('二级类型', $result['merchant_type_name']);
        $this->assertSame(5, $result['merchant_type_id']);
    }

    private function bindAuth(int $companyId): void
    {
        $user = Mockery::mock();
        $user->shouldReceive('get')->with('company_id')->andReturn($companyId);
        $authGuard = Mockery::mock();
        $authGuard->shouldReceive('user')->andReturn($user);
        $this->app->instance('auth', $authGuard);
    }

    private function bindResponseFactory(): void
    {
        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('array')->andReturnUsing(static fn (array $payload): array => $payload);
        $this->app->instance(Factory::class, $factory);
    }

    private function bindRegistryMock(): void
    {
        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getInfoById'])
            ->getMock();

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')
            ->with(MerchantSettlementApply::class)
            ->willReturn($mockRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager'])
            ->getMock();
        $mockRegistry->method('getManager')
            ->with('default')
            ->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }

    private function createServiceWithApplyInfo(array $applyInfo, string $accountId = '100'): MerchantSettlementApplyService
    {
        $this->bindRegistryMock();

        $service = Mockery::mock(MerchantSettlementApplyService::class)->makePartial();
        $service->shouldReceive('getInfoById')
            ->once()
            ->with($accountId)
            ->andReturn($applyInfo);

        return $service;
    }
}
