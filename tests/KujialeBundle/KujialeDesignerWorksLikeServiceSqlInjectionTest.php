<?php

declare(strict_types=1);

namespace Tests\KujialeBundle;

use KujialeBundle\Entities\KujialeDesignerWorksLike;
use KujialeBundle\Services\KujialeDesignerWorksLikeService;

/**
 * SQL 注入修复：KujialeDesignerWorksLikeService::saveLike 参数化 UPDATE。
 * 计划：.tasks/plans/kujiale-like-sql-injection.md TC-KL-01..08
 */
class KujialeDesignerWorksLikeServiceSqlInjectionTest extends \TestCase
{
    /** @var array<int, array{sql: string, params: array, types: array}> */
    private array $captured = [];

    private bool $executeUpdateCalled = false;

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
        $this->executeUpdateCalled = false;
    }

    /**
     * TC-KL-01：like 分支恶意 design_id 绑定进 params，SQL 无注入字面量。
     * #given 恶意 design_id，getInfo 返回空（无已有 like）
     * #when 调用 saveLike(..., 'like')
     * #then SQL 无 extractvalue/SLEEP/` OR `；params 含完整恶意 design_id 字符串
     */
    public function testTcKl01MaliciousDesignIdNotInSqlLiteral(): void
    {
        $maliciousDesignId = "1' AND extractvalue(1,concat(0x7e,version()))-- ";
        $planId = 'plan-normal';

        $this->mockRegistryForLike(getInfoReturn: []);

        $service = new KujialeDesignerWorksLikeService();
        $service->saveLike($maliciousDesignId, $planId, 100, 'like');

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('extractvalue', $last['sql']);
        $this->assertStringNotContainsString('SLEEP', $last['sql']);
        $this->assertStringNotContainsString(' OR ', $last['sql']);

        $this->assertSame(
            [$maliciousDesignId, $planId],
            array_values($last['params'])
        );
    }

    /**
     * TC-KL-02：like 分支恶意 plan_id 绑定进 params，SQL 无注入字面量。
     * #given 恶意 plan_id，getInfo 返回空
     * #when 调用 saveLike(..., 'like')
     * #then SQL 无 extractvalue/SLEEP/` OR `；params 含完整恶意 plan_id 字符串
     */
    public function testTcKl02MaliciousPlanIdNotInSqlLiteral(): void
    {
        $designId = 'design-normal';
        $maliciousPlanId = "x' OR '1'='1";

        $this->mockRegistryForLike(getInfoReturn: []);

        $service = new KujialeDesignerWorksLikeService();
        $service->saveLike($designId, $maliciousPlanId, 100, 'like');

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('extractvalue', $last['sql']);
        $this->assertStringNotContainsString('SLEEP', $last['sql']);
        $this->assertStringNotContainsString(' OR ', $last['sql']);

        $this->assertSame(
            [$designId, $maliciousPlanId],
            array_values($last['params'])
        );
    }

    /**
     * TC-KL-03：unlike 分支恶意 design_id 绑定进 params，SQL 无注入字面量。
     * #given 恶意 design_id，getInfo 返回已有 like
     * #when 调用 saveLike(..., 'unlike')
     * #then SQL 无 extractvalue/SLEEP/` OR `；params 含完整恶意 design_id；SQL 含 -1
     */
    public function testTcKl03MaliciousDesignIdUnlikeNotInSqlLiteral(): void
    {
        $maliciousDesignId = "1' AND extractvalue(1,concat(0x7e,version()))-- ";
        $planId = 'plan-normal';

        $this->mockRegistryForUnlike(getInfoReturn: ['id' => 1]);

        $service = new KujialeDesignerWorksLikeService();
        $service->saveLike($maliciousDesignId, $planId, 100, 'unlike');

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('extractvalue', $last['sql']);
        $this->assertStringNotContainsString('SLEEP', $last['sql']);
        $this->assertStringNotContainsString(' OR ', $last['sql']);
        $this->assertStringContainsString('-1', $last['sql']);

        $this->assertSame(
            [$maliciousDesignId, $planId],
            array_values($last['params'])
        );
    }

    /**
     * TC-KL-04：unlike 分支恶意 plan_id 绑定进 params，SQL 无注入字面量。
     * #given 恶意 plan_id，getInfo 返回已有 like
     * #when 调用 saveLike(..., 'unlike')
     * #then SQL 无 extractvalue/SLEEP/` OR `；params 含完整恶意 plan_id
     */
    public function testTcKl04MaliciousPlanIdUnlikeNotInSqlLiteral(): void
    {
        $designId = 'design-normal';
        $maliciousPlanId = "x' OR '1'='1";

        $this->mockRegistryForUnlike(getInfoReturn: ['id' => 1]);

        $service = new KujialeDesignerWorksLikeService();
        $service->saveLike($designId, $maliciousPlanId, 100, 'unlike');

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('extractvalue', $last['sql']);
        $this->assertStringNotContainsString('SLEEP', $last['sql']);
        $this->assertStringNotContainsString(' OR ', $last['sql']);

        $this->assertSame(
            [$designId, $maliciousPlanId],
            array_values($last['params'])
        );
    }

    /**
     * TC-KL-05：like 合法 ID 经占位符绑定，SQL 含 +1。
     * #given design-abc / plan-xyz，getInfo 返回空
     * #when 调用 saveLike(..., 'like')
     * #then SQL 含 ?；params 精确匹配；SQL 含 +1
     */
    public function testTcKl05LegalIdsLikeUsePlaceholders(): void
    {
        $designId = 'design-abc';
        $planId = 'plan-xyz';

        $this->mockRegistryForLike(getInfoReturn: []);

        $service = new KujialeDesignerWorksLikeService();
        $service->saveLike($designId, $planId, 100, 'like');

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringContainsString('?', $last['sql']);
        $this->assertStringContainsString('+1', $last['sql']);
        $this->assertSame([$designId, $planId], array_values($last['params']));
    }

    /**
     * TC-KL-06：unlike 合法 ID 经占位符绑定，SQL 含 -1。
     * #given design-abc / plan-xyz，getInfo 返回已有 like
     * #when 调用 saveLike(..., 'unlike')
     * #then SQL 含 ? 与 -1；params 精确匹配
     */
    public function testTcKl06LegalIdsUnlikeUsePlaceholders(): void
    {
        $designId = 'design-abc';
        $planId = 'plan-xyz';

        $this->mockRegistryForUnlike(getInfoReturn: ['id' => 1]);

        $service = new KujialeDesignerWorksLikeService();
        $service->saveLike($designId, $planId, 100, 'unlike');

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringContainsString('?', $last['sql']);
        $this->assertStringContainsString('-1', $last['sql']);
        $this->assertSame([$designId, $planId], array_values($last['params']));
    }

    /**
     * TC-KL-07：已有 like + type=like 抛异常且不调用 executeUpdate。
     * #given getInfo 返回已有 like
     * #when 调用 saveLike(..., 'like')
     * #then 抛 ResourceException；executeUpdate 不被调用
     */
    public function testTcKl07DuplicateLikeSkipsExecuteUpdate(): void
    {
        $this->mockRegistryForLike(getInfoReturn: ['id' => 1]);

        $service = new KujialeDesignerWorksLikeService();

        $this->expectException(\Dingo\Api\Exception\ResourceException::class);
        $this->expectExceptionMessage('点赞异常');

        $service->saveLike('design-abc', 'plan-xyz', 100, 'like');

        $this->assertFalse($this->executeUpdateCalled, 'duplicate like should not call executeUpdate');
    }

    /**
     * TC-KL-08：无 like + type=unlike 不调用 executeUpdate。
     * #given getInfo 返回空
     * #when 调用 saveLike(..., 'unlike')
     * #then executeUpdate 不被调用；返回 true
     */
    public function testTcKl08UnlikeWithoutRecordSkipsExecuteUpdate(): void
    {
        $this->mockRegistryForUnlike(getInfoReturn: []);

        $service = new KujialeDesignerWorksLikeService();
        $result = $service->saveLike('design-abc', 'plan-xyz', 100, 'unlike');

        $this->assertTrue($result);
        $this->assertFalse($this->executeUpdateCalled, 'unlike without record should not call executeUpdate');
    }

    /**
     * @param array<string, mixed>|null $getInfoReturn
     */
    private function mockRegistryForLike(?array $getInfoReturn = []): void
    {
        $self = $this;
        $conn = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $conn->method('executeUpdate')->willReturnCallback(
            function ($sql, $params = [], $types = []) use ($self) {
                $self->executeUpdateCalled = true;
                $self->captured[] = compact('sql', 'params', 'types');

                return 1;
            }
        );

        $mockLikeRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getInfo', 'create', 'deleteBy'])
            ->getMock();
        $mockLikeRepo->method('getInfo')->willReturn($getInfoReturn);
        $mockLikeRepo->method('create')->willReturn(true);

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturnCallback(
            function ($class) use ($mockLikeRepo) {
                if ($class === KujialeDesignerWorksLike::class) {
                    return $mockLikeRepo;
                }

                return $this->getMockBuilder(\stdClass::class)->getMock();
            }
        );

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConnection', 'getManager'])
            ->getMock();
        $mockRegistry->method('getConnection')->with('default')->willReturn($conn);
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }

    /**
     * @param array<string, mixed>|null $getInfoReturn
     */
    private function mockRegistryForUnlike(?array $getInfoReturn = ['id' => 1]): void
    {
        $self = $this;
        $conn = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $conn->method('executeUpdate')->willReturnCallback(
            function ($sql, $params = [], $types = []) use ($self) {
                $self->executeUpdateCalled = true;
                $self->captured[] = compact('sql', 'params', 'types');

                return 1;
            }
        );

        $mockLikeRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getInfo', 'create', 'deleteBy'])
            ->getMock();
        $mockLikeRepo->method('getInfo')->willReturn($getInfoReturn);
        $mockLikeRepo->method('deleteBy')->willReturn(true);

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturnCallback(
            function ($class) use ($mockLikeRepo) {
                if ($class === KujialeDesignerWorksLike::class) {
                    return $mockLikeRepo;
                }

                return $this->getMockBuilder(\stdClass::class)->getMock();
            }
        );

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConnection', 'getManager'])
            ->getMock();
        $mockRegistry->method('getConnection')->with('default')->willReturn($conn);
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }
}
