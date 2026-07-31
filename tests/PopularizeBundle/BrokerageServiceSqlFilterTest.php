<?php

declare(strict_types=1);

namespace Tests\PopularizeBundle;

use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Services\BrokerageService;

/**
 * T1/T2：BrokerageService SQL 参数化 + company_id 强制。
 * 计划：.tasks/plans/popularize-raw-sql-sqli-fix.md TC-B-01~06
 */
class BrokerageServiceSqlFilterTest extends \TestCase
{
    /** @var array<int, array{sql: string, params: array, types: array}> */
    private array $captured = [];

    private bool $executeQueryCalled = false;

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
        $this->captured = [];
        $this->executeQueryCalled = false;
        $this->mockRegistryConnection();
        $this->mockLog();
    }

    /**
     * TC-B-01：恶意 dIds 不得出现在 SQL 字面量中，须经 intval 绑定或滤空后安全返回。
     * #given company_id=1，dIds=['1) OR 1=1--']
     * #when getSalesmanBrokerageCount
     * #then SQL 字面量无 OR 1=1；dIds 经 intval 绑定或滤空后安全返回
     */
    public function testTcB01MaliciousDIdsNotInSqlLiteral(): void
    {
        $params = [
            'company_id' => 1,
            'dIds' => ['1) OR 1=1--'],
        ];

        $service = new BrokerageService();
        $result = $service->getSalesmanBrokerageCount($params, 10, 1);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled, 'executeQuery should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('OR 1=1', $last['sql']);

        $paramsBound = $last['params'];
        $hasSafeDIdBinding = false;
        foreach ($paramsBound as $key => $value) {
            if (is_int($value) && $value === 1) {
                $hasSafeDIdBinding = true;
            }
            if (is_array($value) && in_array(1, array_map('intval', $value), true)) {
                $hasSafeDIdBinding = true;
            }
        }
        $this->assertTrue(
            $hasSafeDIdBinding,
            'dIds should be bound as intval-safe value (1), not raw injection string'
        );
    }

    /**
     * TC-B-02：恶意 user_id 须绑定为整型，SQL 字面量无 SLEEP。
     * #given company_id=1，user_id="1 OR SLEEP(3)"
     * #when getSalesmanBrokerageCount
     * #then user_id 为绑定整型；SQL 无 SLEEP
     */
    public function testTcB02MaliciousUserIdBoundAsInteger(): void
    {
        $params = [
            'company_id' => 1,
            'user_id' => '1 OR SLEEP(3)',
        ];

        $service = new BrokerageService();
        $result = $service->getSalesmanBrokerageCount($params, 10, 1);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled);
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('SLEEP', $last['sql']);
        $this->assertArrayHasKey('user_id', $last['params']);
        $this->assertSame(1, $last['params']['user_id']);
    }

    /**
     * TC-B-03：无 company_id 时抛 ResourceException，不调用 executeQuery。
     * #given params 无 company_id
     * #when getSalesmanBrokerageCount
     * #then 抛 ResourceException；不调用 executeQuery
     */
    public function testTcB03MissingCompanyIdThrowsWithoutQuery(): void
    {
        $service = new BrokerageService();

        try {
            $service->getSalesmanBrokerageCount(['user_id' => 1], 10, 1);
            $this->fail('Expected ResourceException');
        } catch (ResourceException $e) {
            $this->assertFalse($this->executeQueryCalled, 'executeQuery must not be called without company_id');
        }
    }

    /**
     * TC-B-04：合法 dIds 时 SQL 含 company_id 条件，params 含 company_id 与 dIds。
     * #given company_id=1，dIds=[10,20]
     * #when getSalesmanBrokerageCount
     * #then SQL 含 bb.company_id = :company_id；params 含 company_id=1 与 dIds
     */
    public function testTcB04LegitimateDIdsIncludeCompanyIdBinding(): void
    {
        $params = [
            'company_id' => 1,
            'dIds' => [10, 20],
        ];

        $service = new BrokerageService();
        $result = $service->getSalesmanBrokerageCount($params, 10, 1);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled);
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringContainsString('bb.company_id = :company_id', $last['sql']);
        $this->assertSame(1, $last['params']['company_id']);

        $boundDIds = [];
        foreach ($last['params'] as $key => $value) {
            if (strpos((string) $key, 'distributor_id_') === 0) {
                $boundDIds[] = $value;
            }
        }
        sort($boundDIds);
        $this->assertSame([10, 20], $boundDIds);
    }

    /**
     * TC-B-05：恶意 mobile 须绑定，SQL 字面量无破引号片段；空 list 不触发 SalespersonService。
     * #given company_id=1，mobile="a' OR '1'='1"
     * #when getSalesmanBrokeragelistsBySql
     * #then mobile 绑定；SQL 无 OR '1'='1 字面量；fetch 空时不访问 Salesperson DB
     */
    public function testTcB05MaliciousMobileBoundNotInSqlLiteral(): void
    {
        $params = [
            'company_id' => 1,
            'mobile' => "a' OR '1'='1",
        ];

        $service = new BrokerageService();
        $result = $service->getSalesmanBrokeragelistsBySql($params, 10, 1);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled, 'executeQuery should be called');
        $this->assertNotEmpty($this->captured);
        $this->assertGreaterThanOrEqual(2, count($this->captured), 'count + list queries expected');

        foreach ($this->captured as $capture) {
            $this->assertStringNotContainsString("OR '1'='1", $capture['sql']);
            $this->assertStringContainsString('bb.company_id = :company_id', $capture['sql']);
            $this->assertArrayHasKey('mobile', $capture['params']);
            $this->assertSame("a' OR '1'='1", $capture['params']['mobile']);
            $this->assertSame(1, $capture['params']['company_id']);
        }

        $this->assertSame([], $result['list']);
    }

    /**
     * TC-B-06：恶意 order_id 须绑定，SQL 无拼接字面量；空 list 不触发 SalespersonService。
     * #given company_id=1，order_id="1' OR '1'='1"
     * #when getSalesmanBrokeragelistsBySql
     * #then order_id 绑定；SQL 无 OR '1'='1 字面量
     */
    public function testTcB06MaliciousOrderIdBoundNotInSqlLiteral(): void
    {
        $params = [
            'company_id' => 1,
            'order_id' => "1' OR '1'='1",
        ];

        $service = new BrokerageService();
        $result = $service->getSalesmanBrokeragelistsBySql($params, 10, 1);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled, 'executeQuery should be called');
        $this->assertNotEmpty($this->captured);
        $this->assertGreaterThanOrEqual(2, count($this->captured), 'count + list queries expected');

        foreach ($this->captured as $capture) {
            $this->assertStringNotContainsString("OR '1'='1", $capture['sql']);
            $this->assertStringContainsString('bb.company_id = :company_id', $capture['sql']);
            $this->assertArrayHasKey('order_id', $capture['params']);
            $this->assertSame("1' OR '1'='1", $capture['params']['order_id']);
            $this->assertSame(1, $capture['params']['company_id']);
        }

        $this->assertSame([], $result['list']);
    }

    /**
     * TC-B-07：非法 groupby 抛 ResourceException，不执行 SQL。
     * #given company_id=1，groupby=evil
     * #when getSalesmanBrokerageCountList
     * #then 抛 ResourceException；不调用 executeQuery
     */
    public function testTcB07InvalidGroupbyThrowsWithoutQuery(): void
    {
        $service = new BrokerageService();

        try {
            $service->getSalesmanBrokerageCountList([
                'company_id' => 1,
                'groupby' => 'evil',
            ], 10, 1);
            $this->fail('Expected ResourceException');
        } catch (ResourceException $e) {
            $this->assertFalse($this->executeQueryCalled, 'executeQuery must not be called with invalid groupby');
        }
    }

    /**
     * TC-B-08：合法 groupby + 恶意 year/month/day 须绑定；SQL 无恶意字面量。
     * #given company_id=1，groupby=distributor_id，year/month/day 恶意串
     * #when getSalesmanBrokerageCountList
     * #then groupby 仅白名单标识符；ymd 绑定；SQL 无恶意字面量；fetch 空时不访问 Salesperson DB
     */
    public function testTcB08MaliciousYmdBoundWithGroupbyWhitelist(): void
    {
        $params = [
            'company_id' => 1,
            'groupby' => 'distributor_id',
            'year' => "2024' OR '1'='1",
            'month' => "2024-07' OR '1'='1",
            'day' => "2024-07-30' OR '1'='1",
        ];

        $service = new BrokerageService();
        $result = $service->getSalesmanBrokerageCountList($params, 10, 1);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled, 'executeQuery should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString("OR '1'='1", $last['sql']);
        $this->assertStringContainsString('bb.company_id = :company_id', $last['sql']);
        $this->assertSame(1, $last['params']['company_id']);
        $this->assertStringContainsString('oo.distributor_id', $last['sql']);
        $this->assertStringNotContainsString("oo.2024' OR '1'='1", $last['sql']);
        $this->assertArrayHasKey('day', $last['params']);
        $this->assertSame("2024-07-30' OR '1'='1", $last['params']['day']);
        $this->assertSame([], $result);
    }

    private function mockRegistryConnection(): void
    {
        $self = $this;
        $conn = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $conn->method('executeQuery')->willReturnCallback(
            function ($sql, $params = [], $types = []) use ($self) {
                $self->executeQueryCalled = true;
                $self->captured[] = [
                    'sql' => (string) $sql,
                    'params' => $params,
                    'types' => $types,
                ];
                $result = $this->getMockBuilder(\stdClass::class)
                    ->addMethods(['fetch', 'fetchAll', 'fetchAssociative', 'fetchAllAssociative'])
                    ->getMock();
                $result->method('fetch')->willReturn([]);
                $result->method('fetchAll')->willReturn([]);
                $result->method('fetchAssociative')->willReturn(false);
                $result->method('fetchAllAssociative')->willReturn([]);

                return $result;
            }
        );

        $mockRepo = $this->getMockBuilder(\stdClass::class)->addMethods(['create'])->getMock();
        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturn($mockRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConnection', 'getManager'])
            ->getMock();
        $mockRegistry->method('getConnection')->with('default')->willReturn($conn);
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }

    private function mockLog(): void
    {
        $log = $this->getMockBuilder(\stdClass::class)->addMethods(['debug'])->getMock();
        $log->method('debug')->willReturn(null);
        $this->app->instance('log', $log);
    }
}
