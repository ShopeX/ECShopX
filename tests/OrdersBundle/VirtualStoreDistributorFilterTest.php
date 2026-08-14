<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use Mockery;
use OrdersBundle\Services\Orders\AbstractNormalOrder;
use OrdersBundle\Services\Orders\NormalOrderService;
use ReflectionMethod;

/**
 * 订单列表/导出虚拟门店 distributor_id 筛选展开 — TC-01～TC-08
 * 计划：.tasks/plans/orders-virtual-store-filter.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class VirtualStoreDistributorFilterTest extends \TestCase
{
    private const COMPANY_ID = 1;
    private const VIRTUAL_STORE_ID = 100;
    private const NORMAL_STORE_ID = 200;

    /**
     * 最小 Lumen 容器，避免 bootstrap/app.php 触发 Doctrine DB 连接。
     */
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRegistryForOrderService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-01 / A1：虚拟店标量 id → filter 含 [0, V]。
     * #given company_id + 标量 distributor_id=V，V 为 distributor_self=1
     * #when expandVirtualStoreDistributorFilter
     * #then distributor_id 等价于 in (0, V)
     */
    public function testTc01VirtualStoreScalarIdExpandsToZeroAndVirtualId(): void
    {
        #given 虚拟门店 distributor_self=1
        $this->mockDistributorInfo(self::VIRTUAL_STORE_ID, ['distributor_self' => 1]);
        $filter = [
            'company_id' => self::COMPANY_ID,
            'distributor_id' => self::VIRTUAL_STORE_ID,
        ];

        #when 展开虚拟店筛选
        $result = $this->invokeExpand($filter);

        #then distributor_id 为 [0, V]
        $this->assertEqualsCanonicalizing(
            [0, self::VIRTUAL_STORE_ID],
            $result['distributor_id']
        );
    }

    /**
     * TC-02 / A2：普通店标量 id → filter 不变。
     * #given distributor_id=S，distributor_self≠1
     * #when expandVirtualStoreDistributorFilter
     * #then distributor_id 仍为标量 S，不含 0
     */
    public function testTc02NormalStoreScalarIdDoesNotExpand(): void
    {
        #given 普通门店 distributor_self=0
        $this->mockDistributorInfo(self::NORMAL_STORE_ID, ['distributor_self' => 0]);
        $filter = [
            'company_id' => self::COMPANY_ID,
            'distributor_id' => self::NORMAL_STORE_ID,
        ];

        #when 展开筛选
        $result = $this->invokeExpand($filter);

        #then 保持标量，不追加 0
        $this->assertSame(self::NORMAL_STORE_ID, $result['distributor_id']);
        $this->assertIsInt($result['distributor_id']);
    }

    /**
     * TC-03 / A3/A7：distributor_id=0 或 '0' → 不展开。
     * #given 显式 distributor_id 为 0 或 '0'
     * #when expandVirtualStoreDistributorFilter
     * #then 仍仅查 0，不展开为虚拟店 id
     */
    public function testTc03ExplicitZeroDistributorIdDoesNotExpand(): void
    {
        foreach ([0, '0'] as $zeroValue) {
            #given 显式 0
            $filter = [
                'company_id' => self::COMPANY_ID,
                'distributor_id' => $zeroValue,
            ];

            #when 展开筛选
            $result = $this->invokeExpand($filter);

            #then 保持原值，不变成数组
            $this->assertSame($zeroValue, $result['distributor_id']);
            $this->assertIsNotArray($result['distributor_id']);
        }
    }

    /**
     * TC-04 / A4：存在 distributor_id|neq → 不展开。
     * #given filter 含 distributor_id|neq（shop 语义）
     * #when expandVirtualStoreDistributorFilter
     * #then 不追加 0，distributor_id 保持标量
     */
    public function testTc04DistributorIdNeqPreventsExpansion(): void
    {
        #given shop 语义：neq=0 且标量虚拟店 id
        $this->mockDistributorInfo(self::VIRTUAL_STORE_ID, ['distributor_self' => 1]);
        $filter = [
            'company_id' => self::COMPANY_ID,
            'distributor_id|neq' => 0,
            'distributor_id' => self::VIRTUAL_STORE_ID,
        ];

        #when 展开筛选
        $result = $this->invokeExpand($filter);

        #then 不展开，仍为标量 V
        $this->assertSame(self::VIRTUAL_STORE_ID, $result['distributor_id']);
        $this->assertArrayHasKey('distributor_id|neq', $result);
    }

    /**
     * TC-05 / A6：distributor_id|in 含虚拟店 → 追加 0 并去重。
     * #given distributor_id|in = [S, V]，V 为虚拟店
     * #when expandVirtualStoreDistributorFilter
     * #then IN 列表含 0 且去重
     */
    public function testTc05DistributorIdInWithVirtualStoreAppendsZeroDeduped(): void
    {
        #given staff 多店 in 含虚拟店
        $this->mockDistributorInfos([
            self::VIRTUAL_STORE_ID => ['distributor_self' => 1],
            self::NORMAL_STORE_ID => ['distributor_self' => 0],
        ]);
        $filter = [
            'company_id' => self::COMPANY_ID,
            'distributor_id|in' => [self::NORMAL_STORE_ID, self::VIRTUAL_STORE_ID],
        ];

        #when 展开筛选
        $result = $this->invokeExpand($filter);

        #then 追加 0 并去重
        $this->assertEqualsCanonicalizing(
            [0, self::NORMAL_STORE_ID, self::VIRTUAL_STORE_ID],
            $result['distributor_id|in']
        );
    }

    /**
     * TC-06 / A8：未知/非 virtual id → 不展开。
     * #given distributor_id=999 不存在或非 virtual
     * #when expandVirtualStoreDistributorFilter
     * #then 等同原等值，保持标量
     */
    public function testTc06UnknownOrNonVirtualIdDoesNotExpand(): void
    {
        #given 未知与非 virtual 两种边界
        $this->mockDistributorInfos([
            999 => [],
            888 => ['distributor_self' => 0],
        ]);

        #when 未知 id
        $result = $this->invokeExpand([
            'company_id' => self::COMPANY_ID,
            'distributor_id' => 999,
        ]);
        #then 保持标量 999
        $this->assertSame(999, $result['distributor_id']);

        #when 存在但非 virtual
        $result2 = $this->invokeExpand([
            'company_id' => self::COMPANY_ID,
            'distributor_id' => 888,
        ]);
        #then 保持标量 888
        $this->assertSame(888, $result2['distributor_id']);
    }

    /**
     * TC-07 / A5：getOrderList、countOrderNum、getOrderItemCount 三入口展开形态一致。
     * #given 虚拟店 filter
     * #when 检查三入口均调用 expandVirtualStoreDistributorFilter
     * #then 三入口方法体均含 expand 调用（与 expand 输出 [0,V] 一致）
     */
    public function testTc07ListAndCountEntryPointsApplySameVirtualStoreExpansion(): void
    {
        #given expand 对虚拟店产出 [0, V]
        $this->mockDistributorInfo(self::VIRTUAL_STORE_ID, ['distributor_self' => 1]);
        $filter = [
            'company_id' => self::COMPANY_ID,
            'distributor_id' => self::VIRTUAL_STORE_ID,
        ];
        $expanded = $this->invokeExpand($filter);
        $this->assertEqualsCanonicalizing([0, self::VIRTUAL_STORE_ID], $expanded['distributor_id']);

        #when 检查列表/计数三入口
        foreach (['getOrderList', 'countOrderNum', 'getOrderItemCount'] as $method) {
            #then 各入口查库前调用 expandVirtualStoreDistributorFilter
            $body = $this->methodBody(AbstractNormalOrder::class, $method);
            $this->assertStringContainsString(
                'expandVirtualStoreDistributorFilter',
                $body,
                sprintf('%s must invoke expandVirtualStoreDistributorFilter before query', $method)
            );
        }
    }

    /**
     * TC-08 / A5：getOrderItemList 导出行数据路径与 TC-07 展开形态一致。
     * #given 虚拟店 filter
     * #when 检查 getOrderItemList 调用 expand
     * #then 方法体含 expandVirtualStoreDistributorFilter，展开形态与 TC-07 一致
     */
    public function testTc08GetOrderItemListAppliesSameVirtualStoreExpansionAsListPaths(): void
    {
        #given expand 对虚拟店产出 [0, V]（与 TC-07 一致）
        $this->mockDistributorInfo(self::VIRTUAL_STORE_ID, ['distributor_self' => 1]);
        $filter = [
            'company_id' => self::COMPANY_ID,
            'distributor_id' => self::VIRTUAL_STORE_ID,
        ];
        $expanded = $this->invokeExpand($filter);
        $this->assertEqualsCanonicalizing([0, self::VIRTUAL_STORE_ID], $expanded['distributor_id']);

        #when 检查导出行数据入口 getOrderItemList
        $body = $this->methodBody(AbstractNormalOrder::class, 'getOrderItemList');

        #then 查库前调用 expandVirtualStoreDistributorFilter
        $this->assertStringContainsString(
            'expandVirtualStoreDistributorFilter',
            $body,
            'getOrderItemList must invoke expandVirtualStoreDistributorFilter before query'
        );
    }

    /**
     * @param array<string, mixed> $filter
     * @return array<string, mixed>
     */
    private function invokeExpand(array $filter): array
    {
        $service = new NormalOrderService();
        $method = new ReflectionMethod(AbstractNormalOrder::class, 'expandVirtualStoreDistributorFilter');
        $method->setAccessible(true);

        /** @var array<string, mixed> $result */
        $result = $method->invoke($service, $filter);

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mockDistributorInfo(int $distributorId, array $row): void
    {
        $this->mockDistributorInfos([$distributorId => $row]);
    }

    /**
     * @param array<int, array<string, mixed>> $distributors
     */
    private function mockDistributorInfos(array $distributors): void
    {
        $mock = Mockery::mock('overload:DistributionBundle\Services\DistributorService');
        foreach ($distributors as $distributorId => $row) {
            $mock->shouldReceive('getInfoSimple')
                ->with([
                    'distributor_id' => $distributorId,
                    'company_id' => self::COMPANY_ID,
                ])
                ->andReturn(array_merge(
                    ['distributor_id' => $distributorId, 'company_id' => self::COMPANY_ID],
                    $row
                ));
        }
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }

    private function mockRegistryForOrderService(): void
    {
        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create', 'getList', 'count', 'get', 'getInfo', 'update', 'getLists', 'getRow'])
            ->getMock();
        $mockRepo->method('create')->willReturn(['cancel_id' => 1]);
        $mockRepo->method('count')->willReturn(0);
        $mockRepo->method('getList')->willReturn([]);
        $mockRepo->method('getLists')->willReturn([]);

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository', 'getConnection'])
            ->getMock();
        $mockManager->method('getRepository')->willReturn($mockRepo);

        $mockConnection = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['createQueryBuilder'])
            ->getMock();
        $mockManager->method('getConnection')->willReturn($mockConnection);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager'])
            ->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }
}
