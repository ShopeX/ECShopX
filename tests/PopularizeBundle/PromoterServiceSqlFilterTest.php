<?php

declare(strict_types=1);

namespace Tests\PopularizeBundle;

use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Services\PromoterService;

/**
 * T5：PromoterService S4/S5/S6 SQL 参数化 + company_id（方案 A）。
 * 计划：.tasks/plans/popularize-raw-sql-sqli-fix.md TC-P-01~05
 */
class PromoterServiceSqlFilterTest extends \TestCase
{
    /** @var array<int, array{sql: string, params: array, types: array}> */
    private array $captured = [];

    private bool $executeQueryCalled = false;

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
     * TC-P-01：恶意 date 须绑定，SQL 无破引号，company_id 来自 authInfo。
     * #given authInfo company_id=1，params date 恶意串
     * #when getSalesmanCount
     * #then date 绑定；SQL 无破引号；:company_id=1
     */
    public function testTcP01MaliciousDateBoundWithCompanyIdFromAuth(): void
    {
        $authInfo = ['user_id' => 9, 'company_id' => 1];
        $params = [
            'datetype' => 'm',
            'date' => "2024-01' OR '1'='1",
        ];

        $service = new PromoterService();
        $result = $service->getSalesmanCount($authInfo, $params);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled, 'executeQuery should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString("OR '1'='1", $last['sql']);
        $this->assertStringContainsString('bb.company_id = :company_id', $last['sql']);
        $this->assertSame(1, $last['params']['company_id']);
        $this->assertArrayHasKey('date_val', $last['params']);
        $this->assertSame("2024-01' OR '1'='1", $last['params']['date_val']);
    }

    /**
     * TC-P-02：恶意 distributor_id 须绑定为整型。
     * #given authInfo company_id=1，distributor_id 恶意串
     * #when getSalesmanStatic
     * #then distributor_id 绑定整型；SQL 无 OR 1=1 字面量
     */
    public function testTcP02MaliciousDistributorIdBoundAsInteger(): void
    {
        $authInfo = ['user_id' => 9, 'company_id' => 1];
        $params = [
            'distributor_id' => '1 OR 1=1',
        ];

        $service = new PromoterService();
        $result = $service->getSalesmanStatic($authInfo, $params);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled, 'executeQuery should be called');
        $this->assertNotEmpty($this->captured);

        $brokerageQuery = $this->captured[0];
        $this->assertStringNotContainsString('OR 1=1', $brokerageQuery['sql']);
        $this->assertArrayHasKey('distributor_id', $brokerageQuery['params']);
        $this->assertSame(1, $brokerageQuery['params']['distributor_id']);
        $this->assertStringContainsString('bb.company_id = :company_id', $brokerageQuery['sql']);
        $this->assertSame(1, $brokerageQuery['params']['company_id']);
    }

    /**
     * TC-P-03：无 company_id 时抛 ResourceException，不执行 SQL。
     * #given authInfo 无 company_id，params 空
     * #when getSalesmanCount
     * #then 抛 ResourceException；不调用 executeQuery
     */
    public function testTcP03MissingCompanyIdThrowsWithoutQuery(): void
    {
        $authInfo = ['user_id' => 9];
        $params = [];

        $service = new PromoterService();

        try {
            $service->getSalesmanCount($authInfo, $params);
            $this->fail('Expected ResourceException');
        } catch (ResourceException $e) {
            $this->assertFalse($this->executeQueryCalled, 'executeQuery must not be called without company_id');
        }
    }

    /**
     * TC-P-04：合法 datetype/tab 时 SQL 绑定与白名单；S6 仅接收占位符+bind。
     * #given authInfo company_id=1，合法 datetype/tab/date
     * #when getSalesmanStatic
     * #then SQL 含 :company_id；tab 白名单；S6 无用户字面量 SQL 片段
     */
    public function testTcP04LegitimateDatetypeTabUsesBindingAndS6Placeholders(): void
    {
        $authInfo = ['user_id' => 9, 'company_id' => 1];
        $params = [
            'datetype' => 'm',
            'date' => '2024-01',
            'tab' => 'lv1',
        ];

        $service = new PromoterService();
        $result = $service->getSalesmanStatic($authInfo, $params);

        $this->assertIsArray($result);
        $this->assertTrue($this->executeQueryCalled);
        $this->assertGreaterThanOrEqual(2, count($this->captured), 'brokerage + promoter queries expected');

        $brokerageQuery = $this->captured[0];
        $this->assertStringContainsString('bb.company_id = :company_id', $brokerageQuery['sql']);
        $this->assertSame(1, $brokerageQuery['params']['company_id']);
        $this->assertArrayHasKey('date_val', $brokerageQuery['params']);
        $this->assertSame('2024-01', $brokerageQuery['params']['date_val']);
        $this->assertStringContainsString("'first_level'", $brokerageQuery['sql']);
        $this->assertStringNotContainsString('2024-01', $brokerageQuery['sql']);

        $promoterQuery = $this->captured[1];
        $this->assertStringContainsString('popularize_promoter', $promoterQuery['sql']);
        $this->assertStringContainsString(':date_val', $promoterQuery['sql']);
        $this->assertStringNotContainsString("'2024-01'", $promoterQuery['sql']);
        $this->assertSame(1, $promoterQuery['params']['company_id']);
        $this->assertSame('2024-01', $promoterQuery['params']['date_val']);
    }

    /**
     * TC-P-05：getSalesmanStatic→S6 含 popularize_promoter 与 company_id 绑定。
     * #given authInfo company_id=1，合法 date
     * #when getSalesmanStatic
     * #then S6 SQL 含 popularize_promoter 与 bb.company_id = :company_id；bind 含 company_id
     */
    public function testTcP05SalesPromotersStaticIncludesCompanyIdBinding(): void
    {
        $authInfo = ['user_id' => 9, 'company_id' => 1];
        $params = [
            'datetype' => 'd',
            'date' => '2024-01-15',
        ];

        $service = new PromoterService();
        $result = $service->getSalesmanStatic($authInfo, $params);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($this->captured));

        $promoterQuery = end($this->captured);
        $this->assertStringContainsString('popularize_promoter', $promoterQuery['sql']);
        $this->assertStringContainsString('bb.company_id = :company_id', $promoterQuery['sql']);
        $this->assertSame(1, $promoterQuery['params']['company_id']);
        $this->assertArrayHasKey('date_val', $promoterQuery['params']);
        $this->assertSame('2024-01-15', $promoterQuery['params']['date_val']);
        $this->assertStringNotContainsString("'2024-01-15'", $promoterQuery['sql']);
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
