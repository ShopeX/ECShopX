<?php

declare(strict_types=1);

namespace Tests\DistributionBundle;

use DistributionBundle\Repositories\DistributorRepository;
use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;

/**
 * SQL 注入修复：DistributorRepository lat/lng Haversine 参数化。
 * 计划：.tasks/plans/fix-distributor-lat-lng-sqli.md TC-LL-01..08
 */
class DistributorRepositoryLatLngSqlInjectionTest extends \TestCase
{
    /** @var array<int, array{sql: string, params: array, types: array}> */
    private array $capturedExecutes = [];

    private int $executeCallCount = 0;

    private string $lastSelectSql = '';

    /** @var array<string, mixed> */
    private array $mainQbParams = [];

    /** @var array<string, mixed> */
    private array $mainQbParamTypes = [];

    private bool $countQbSetParametersCalled = false;

    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->capturedExecutes = [];
        $this->executeCallCount = 0;
        $this->lastSelectSql = '';
        $this->mainQbParams = [];
        $this->mainQbParamTypes = [];
        $this->countQbSetParametersCalled = false;
    }

    /**
     * TC-LL-01：lists() 恶意 lat 不进入 SQL 字面量（或校验拒绝）。
     * #given 恶意 lat + 合法 lng
     * #when lists()
     * #then 无 extractvalue/SLEEP/` OR ` 字面量，或未执行 SQL 并抛 ResourceException
     */
    public function testTcLl01MaliciousLatNotInSqlLiteral(): void
    {
        $maliciousLat = "1' AND extractvalue(1,concat(0x7e,version()))-- ";
        $this->mockRegistryForLists(withTotalCount: false);

        $repo = $this->createRepository();

        try {
            $repo->lists([
                'merchant_id' => 1,
                'company_id' => 1,
                'lat' => $maliciousLat,
                'lng' => '121.4',
            ], [], 10, 1, false);
            $this->fail('Expected ResourceException for malicious lat');
        } catch (ResourceException $e) {
            $this->assertSame('经纬度范围错误.', $e->getMessage());
            $this->assertSame(0, $this->executeCallCount, 'SQL must not execute for invalid lat');
        }
    }

    /**
     * TC-LL-02：lists() 恶意 lng 不进入 SQL 字面量（或校验拒绝）。
     */
    public function testTcLl02MaliciousLngNotInSqlLiteral(): void
    {
        $maliciousLng = "1' OR '1'='1";
        $this->mockRegistryForLists(withTotalCount: false);

        $repo = $this->createRepository();

        try {
            $repo->lists([
                'merchant_id' => 1,
                'company_id' => 1,
                'lat' => '31.17',
                'lng' => $maliciousLng,
            ], [], 10, 1, false);
            $this->fail('Expected ResourceException for malicious lng');
        } catch (ResourceException $e) {
            $this->assertSame('经纬度范围错误.', $e->getMessage());
            $this->assertSame(0, $this->executeCallCount);
        }
    }

    /**
     * TC-LL-03：合法 lat/lng 使用命名参数绑定 float。
     */
    public function testTcLl03LegitimateLatLngUsesNamedFloatParameters(): void
    {
        $this->mockRegistryForLists(withTotalCount: false);

        $repo = $this->createRepository();
        $repo->lists([
            'merchant_id' => 1,
            'company_id' => 1,
            'lat' => '31.17779',
            'lng' => '121.41795',
        ], [], 10, 1, false);

        $this->assertStringContainsString(':user_lat', $this->lastSelectSql);
        $this->assertStringContainsString(':user_lng', $this->lastSelectSql);
        $this->assertStringNotContainsString('31.17779', $this->lastSelectSql);
        $this->assertSame(31.17779, $this->mainQbParams['user_lat']);
        $this->assertSame(121.41795, $this->mainQbParams['user_lng']);
    }

    /**
     * TC-LL-04：isTotalCount=true 时 count 子查询复制参数。
     */
    public function testTcLl04TotalCountSubqueryCopiesBoundParameters(): void
    {
        $this->mockRegistryForLists(withTotalCount: true);

        $repo = $this->createRepository();
        $repo->lists([
            'merchant_id' => 1,
            'company_id' => 1,
            'lat' => '31.17779',
            'lng' => '121.41795',
        ], [], 10, 1, true);

        $this->assertTrue($this->countQbSetParametersCalled);
        $this->assertGreaterThanOrEqual(2, $this->executeCallCount);
        $this->assertSame(31.17779, $this->mainQbParams['user_lat']);
        $this->assertSame(121.41795, $this->mainQbParams['user_lng']);
    }

    /**
     * TC-LL-05：无 lat/lng 时不添加 distance 表达式。
     */
    public function testTcLl05MissingLatLngSkipsDistanceExpression(): void
    {
        $this->mockRegistryForLists(withTotalCount: false);

        $repo = $this->createRepository();
        $repo->lists([
            'merchant_id' => 1,
            'company_id' => 1,
        ], [], 10, 1, false);

        $this->assertStringNotContainsString('AS distance', $this->lastSelectSql);
        $this->assertArrayNotHasKey('user_lat', $this->mainQbParams);
    }

    /**
     * TC-LL-06：越界或非数字坐标抛 ResourceException。
     */
    public function testTcLl06OutOfRangeLatThrowsResourceException(): void
    {
        $this->mockRegistryForLists(withTotalCount: false);

        $repo = $this->createRepository();

        try {
            $repo->lists([
                'merchant_id' => 1,
                'company_id' => 1,
                'lat' => '91',
                'lng' => '121',
            ], [], 10, 1, false);
            $this->fail('Expected ResourceException for lat=91');
        } catch (ResourceException $e) {
            $this->assertSame('经纬度范围错误.', $e->getMessage());
        }
    }

    /**
     * TC-LL-07a：getNearDistributorList 恶意坐标拒绝。
     */
    public function testTcLl07aMaliciousNearListCoordinatesRejected(): void
    {
        $this->mockRegistryForNearList();

        $repo = $this->createRepository();

        try {
            $repo->getNearDistributorList(['company_id' => 1], "1' OR '1'='1", '121');
            $this->fail('Expected ResourceException');
        } catch (ResourceException $e) {
            $this->assertSame('经纬度范围错误.', $e->getMessage());
            $this->assertSame(0, $this->executeCallCount);
        }
    }

    /**
     * TC-LL-07b：getNearDistributorList 合法坐标参数化。
     */
    public function testTcLl07bLegitimateNearListUsesNamedParameters(): void
    {
        $this->mockRegistryForNearList();

        $repo = $this->createRepository();
        $repo->getNearDistributorList(['company_id' => 1], 31.17779, 121.41795);

        $this->assertStringContainsString(':user_lat', $this->lastSelectSql);
        $this->assertStringContainsString(':user_lng', $this->lastSelectSql);
        $this->assertSame(31.17779, $this->mainQbParams['user_lat']);
        $this->assertSame(121.41795, $this->mainQbParams['user_lng']);
    }

    private function createRepository(): DistributorRepository
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);

        $langService = $this->getMockBuilder(MultiLangOutsideItemService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getListAddLang'])
            ->getMock();
        $langService->method('getListAddLang')->willReturnArgument(0);

        $repo = $this->getMockBuilder(DistributorRepository::class)
            ->setConstructorArgs([$em, $metadata])
            ->onlyMethods(['getLangService', 'getLang'])
            ->getMock();
        $repo->method('getLangService')->willReturn($langService);
        $repo->method('getLang')->willReturn('zh-CN');

        return $repo;
    }

    private function mockRegistryForLists(bool $withTotalCount = true): void
    {
        $self = $this;
        $expr = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['eq', 'in', 'like', 'literal', 'orX', 'andX', 'gt', 'lte'])
            ->getMock();
        $expr->method('eq')->willReturn('eq_expr');
        $expr->method('in')->willReturn('in_expr');
        $expr->method('like')->willReturn('like_expr');
        $expr->method('literal')->willReturnArgument(0);
        $expr->method('orX')->willReturn('or_x_expr');
        $expr->method('andX')->willReturn('and_x_expr');
        $expr->method('gt')->willReturn('gt_expr');
        $expr->method('lte')->willReturn('lte_expr');

        $mainQb = $this->getMockBuilder(\stdClass::class)
            ->addMethods([
                'select', 'from', 'leftJoin', 'andWhere', 'expr', 'having', 'getSql',
                'setParameter', 'getParameters', 'getParameterTypes', 'setParameters',
                'addOrderBy', 'setFirstResult', 'setMaxResults', 'execute',
            ])
            ->getMock();

        $mainQb->method('select')->willReturnCallback(function (string $select) use ($self, $mainQb) {
            $self->lastSelectSql = $select;
            return $mainQb;
        });
        $mainQb->method('from')->willReturnSelf();
        $mainQb->method('leftJoin')->willReturnSelf();
        $mainQb->method('andWhere')->willReturnSelf();
        $mainQb->method('expr')->willReturn($expr);
        $mainQb->method('having')->willReturnSelf();
        $mainQb->method('getSql')->willReturnCallback(function () use ($self) {
            return 'SELECT ' . $self->lastSelectSql . ' FROM distribution_distributor';
        });
        $mainQb->method('setParameter')->willReturnCallback(function ($key, $value, $type = null) use ($self, $mainQb) {
            $self->mainQbParams[$key] = $value;
            if ($type !== null) {
                $self->mainQbParamTypes[$key] = $type;
            }
            return $mainQb;
        });
        $mainQb->method('getParameters')->willReturnCallback(function () use ($self) {
            return $self->mainQbParams;
        });
        $mainQb->method('getParameterTypes')->willReturnCallback(function () use ($self) {
            return $self->mainQbParamTypes;
        });
        $mainQb->method('setParameters')->willReturnCallback(function (array $params, array $types = []) use ($self, $mainQb) {
            $self->mainQbParams = $params;
            $self->mainQbParamTypes = $types;
            return $mainQb;
        });
        $mainQb->method('addOrderBy')->willReturnSelf();
        $mainQb->method('setFirstResult')->willReturnSelf();
        $mainQb->method('setMaxResults')->willReturnSelf();

        $listResult = $this->getMockBuilder(\stdClass::class)->addMethods(['fetchAll'])->getMock();
        $listResult->method('fetchAll')->willReturn([]);

        $countResult = $this->getMockBuilder(\stdClass::class)->addMethods(['fetchColumn'])->getMock();
        $countResult->method('fetchColumn')->willReturn(0);

        $countQb = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select', 'from', 'setParameters', 'execute'])
            ->getMock();
        $countQb->method('select')->willReturnSelf();
        $countQb->method('from')->willReturnSelf();
        $countQb->method('setParameters')->willReturnCallback(function (array $params, array $types = []) use ($self, $countQb) {
            $self->countQbSetParametersCalled = true;
            $self->mainQbParams = $params;
            $self->mainQbParamTypes = $types;
            return $countQb;
        });
        $countQb->method('execute')->willReturnCallback(function () use ($self, $countResult) {
            $self->executeCallCount++;
            $self->capturedExecutes[] = [
                'sql' => 'count_subquery',
                'params' => $self->mainQbParams,
                'types' => $self->mainQbParamTypes,
            ];
            return $countResult;
        });

        $mainQb->method('execute')->willReturnCallback(function () use ($self, $listResult, $withTotalCount) {
            $self->executeCallCount++;
            $self->capturedExecutes[] = [
                'sql' => $self->lastSelectSql,
                'params' => $self->mainQbParams,
                'types' => $self->mainQbParamTypes,
            ];
            return $listResult;
        });

        $qbCreateCount = 0;
        $conn = $this->getMockBuilder(\stdClass::class)->addMethods(['createQueryBuilder'])->getMock();
        $conn->method('createQueryBuilder')->willReturnCallback(function () use (&$qbCreateCount, $mainQb, $countQb, $withTotalCount) {
            $qbCreateCount++;
            if ($withTotalCount && $qbCreateCount === 2) {
                return $countQb;
            }
            return $mainQb;
        });

        $merchantRepo = new class {
            public string $table = 'merchant';
        };

        $manager = $this->getMockBuilder(\stdClass::class)->addMethods(['getRepository'])->getMock();
        $manager->method('getRepository')->willReturn($merchantRepo);

        $registry = $this->getMockBuilder(\stdClass::class)->addMethods(['getConnection', 'getManager'])->getMock();
        $registry->method('getConnection')->with('default')->willReturn($conn);
        $registry->method('getManager')->with('default')->willReturn($manager);

        $this->app->instance('registry', $registry);
    }

    private function mockRegistryForNearList(): void
    {
        $self = $this;
        $expr = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['eq', 'in', 'like', 'literal', 'orX', 'andX', 'gt', 'lte'])
            ->getMock();
        $expr->method('eq')->willReturn('eq_expr');
        $expr->method('in')->willReturn('in_expr');
        $expr->method('like')->willReturn('like_expr');
        $expr->method('literal')->willReturnArgument(0);
        $expr->method('orX')->willReturn('or_x_expr');
        $expr->method('andX')->willReturn('and_x_expr');
        $expr->method('gt')->willReturn('gt_expr');
        $expr->method('lte')->willReturn('lte_expr');

        $mainQb = $this->getMockBuilder(\stdClass::class)
            ->addMethods([
                'select', 'from', 'andWhere', 'expr', 'setParameter',
                'orderBy', 'setFirstResult', 'setMaxResults', 'execute',
            ])
            ->getMock();

        $mainQb->method('select')->willReturnCallback(function (string $select) use ($self, $mainQb) {
            $self->lastSelectSql = $select;
            return $mainQb;
        });
        $mainQb->method('from')->willReturnSelf();
        $mainQb->method('andWhere')->willReturnSelf();
        $mainQb->method('expr')->willReturn($expr);
        $mainQb->method('setParameter')->willReturnCallback(function ($key, $value, $type = null) use ($self, $mainQb) {
            $self->mainQbParams[$key] = $value;
            if ($type !== null) {
                $self->mainQbParamTypes[$key] = $type;
            }
            return $mainQb;
        });
        $mainQb->method('orderBy')->willReturnSelf();
        $mainQb->method('setFirstResult')->willReturnSelf();
        $mainQb->method('setMaxResults')->willReturnSelf();

        $listResult = $this->getMockBuilder(\stdClass::class)->addMethods(['fetchAll'])->getMock();
        $listResult->method('fetchAll')->willReturn([]);

        $mainQb->method('execute')->willReturnCallback(function () use ($self, $listResult) {
            $self->executeCallCount++;
            return $listResult;
        });

        $conn = $this->getMockBuilder(\stdClass::class)->addMethods(['createQueryBuilder'])->getMock();
        $conn->method('createQueryBuilder')->willReturn($mainQb);

        $registry = $this->getMockBuilder(\stdClass::class)->addMethods(['getConnection'])->getMock();
        $registry->method('getConnection')->with('default')->willReturn($conn);

        $this->app->instance('registry', $registry);
    }
}
