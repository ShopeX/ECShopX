<?php

declare(strict_types=1);

namespace Tests\MembersBundle;

use MembersBundle\Entities\MemberRelTags;
use MembersBundle\Services\MemberTagsService;

/**
 * T1：MemberTagsService SQL 参数化 + tag_id/company_id/cout 绑定。
 * 计划：.tasks/plans/member-tags-sqli-fix.md TC-MT-01~06
 */
class MemberTagsServiceSqlInjectionTest extends \TestCase
{
    /** @var array<int, array{sql: string, params: array, types: array}> */
    private array $captured = [];

    private bool $executeUpdateCalled = false;

    private int $deleteByCallCount = 0;

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
        $this->deleteByCallCount = 0;
        $this->mockRegistryConnection();
    }

    /**
     * TC-MT-01：恶意 tag_id 不得出现在 SQL 字面量；须整型绑定。
     * #given tag_id 含 extractvalue 注入串，company_id=1
     * #when Reflection 调用 tagCountReduce
     * #then SQL 无 extractvalue/SLEEP/OR 注入字面量；params 中 tag_id 为整型 1
     */
    public function testTcMt01MaliciousTagIdNotInSqlLiteral(): void
    {
        $maliciousTagId = '1 AND extractvalue(1,concat(0x7e,version()))';
        $data = [
            'tag_id' => $maliciousTagId,
            'company_id' => 1,
        ];

        $service = new MemberTagsService();
        $method = new \ReflectionMethod(MemberTagsService::class, 'tagCountReduce');
        $method->setAccessible(true);
        $method->invoke($service, $data);

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('extractvalue', $last['sql']);
        $this->assertStringNotContainsString('SLEEP', $last['sql']);
        $this->assertStringNotContainsString(' OR ', $last['sql']);

        $tagIdBoundAsInt = false;
        if (isset($last['params']['tag_id']) && $last['params']['tag_id'] === 1) {
            $tagIdBoundAsInt = true;
        }
        if (isset($last['params'][1]) && $last['params'][1] === 1) {
            $tagIdBoundAsInt = true;
        }
        $this->assertTrue(
            $tagIdBoundAsInt,
            'tag_id should be bound as intval-safe value (1), not raw injection string'
        );
    }

    /**
     * TC-MT-02：恶意 company_id 不得出现在 SQL 字面量；须整型绑定。
     * #given company_id 含 extractvalue 注入串，tag_id=10
     * #when Reflection 调用 tagCountReduce
     * #then SQL 无 extractvalue/SLEEP/OR 注入字面量；params 中 company_id 为整型 1
     */
    public function testTcMt02MaliciousCompanyIdNotInSqlLiteral(): void
    {
        $maliciousCompanyId = '1 AND extractvalue(1,concat(0x7e,version()))';
        $data = [
            'tag_id' => 10,
            'company_id' => $maliciousCompanyId,
        ];

        $service = new MemberTagsService();
        $method = new \ReflectionMethod(MemberTagsService::class, 'tagCountReduce');
        $method->setAccessible(true);
        $method->invoke($service, $data);

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('extractvalue', $last['sql']);
        $this->assertStringNotContainsString('SLEEP', $last['sql']);
        $this->assertStringNotContainsString(' OR ', $last['sql']);

        $companyIdBoundAsInt = false;
        if (isset($last['params']['company_id']) && $last['params']['company_id'] === 1) {
            $companyIdBoundAsInt = true;
        }
        if (isset($last['params'][2]) && $last['params'][2] === 1) {
            $companyIdBoundAsInt = true;
        }
        $this->assertTrue(
            $companyIdBoundAsInt,
            'company_id should be bound as intval-safe value (1), not raw injection string'
        );
    }

    /**
     * TC-MT-03：合法 tag_id/company_id/cout 经占位符绑定。
     * #given tag_id=10、company_id=1、cout=1
     * #when Reflection 调用 tagCountReduce
     * #then executeUpdate 被调用；SQL 含 ? 占位符；params 为对应整型
     */
    public function testTcMt03LegalParamsUsePlaceholders(): void
    {
        $data = [
            'tag_id' => 10,
            'company_id' => 1,
        ];

        $service = new MemberTagsService();
        $method = new \ReflectionMethod(MemberTagsService::class, 'tagCountReduce');
        $method->setAccessible(true);
        $method->invoke($service, $data, 1);

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringContainsString('?', $last['sql']);
        $this->assertSame([1, 10, 1], array_values($last['params']));
    }

    /**
     * TC-MT-04：空/缺失 tag_id 时不调用 executeUpdate。
     * #given tag_id 为空字符串或缺失 key
     * #when Reflection 调用 tagCountReduce
     * #then executeUpdate 不被调用
     */
    public function testTcMt04EmptyTagIdSkipsExecuteUpdate(): void
    {
        $service = new MemberTagsService();
        $method = new \ReflectionMethod(MemberTagsService::class, 'tagCountReduce');
        $method->setAccessible(true);

        $method->invoke($service, ['tag_id' => '', 'company_id' => 1]);
        $this->assertFalse($this->executeUpdateCalled, 'empty tag_id should skip executeUpdate');

        $this->executeUpdateCalled = false;
        $method->invoke($service, ['company_id' => 1]);
        $this->assertFalse($this->executeUpdateCalled, 'missing tag_id should skip executeUpdate');
    }

    /**
     * TC-MT-05：cout=5 经参数绑定，非拼进 SQL 字面量。
     * #given tag_id=10、company_id=1、cout=5
     * #when Reflection 调用 tagCountReduce
     * #then params 含 5；SQL 无 self_tag_count-5 无占位符形式
     */
    public function testTcMt05CoutBoundAsParameter(): void
    {
        $data = [
            'tag_id' => 10,
            'company_id' => 1,
        ];

        $service = new MemberTagsService();
        $method = new \ReflectionMethod(MemberTagsService::class, 'tagCountReduce');
        $method->setAccessible(true);
        $method->invoke($service, $data, 5);

        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('self_tag_count-5', $last['sql']);
        $this->assertContains(5, array_values($last['params']));
    }

    /**
     * TC-MT-06：CNVD 路径 delRelMemberTag 恶意 tag_id 安全 + deleteBy 被调用。
     * #given 恶意 tag_id，deleteBy 返回 false（跳过 push）
     * #when 调用 public delRelMemberTag(1, 100, maliciousTagId)
     * #then sink SQL/params 安全；deleteBy 至少调用 1 次
     */
    public function testTcMt06DelRelMemberTagCnvdPathSafe(): void
    {
        $this->captured = [];
        $this->executeUpdateCalled = false;
        $this->deleteByCallCount = 0;
        $this->mockRegistryForDelRelMemberTag();

        $maliciousTagId = '1 AND extractvalue(1,concat(0x7e,version()))';
        $service = new MemberTagsService();
        $result = $service->delRelMemberTag(1, 100, $maliciousTagId);

        $this->assertFalse($result);
        $this->assertGreaterThanOrEqual(1, $this->deleteByCallCount, 'deleteBy should be called at least once');
        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called via tagCountReduce');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringNotContainsString('extractvalue', $last['sql']);
        $this->assertStringNotContainsString('SLEEP', $last['sql']);
        $this->assertStringNotContainsString(' OR ', $last['sql']);

        $tagIdBoundAsInt = false;
        if (isset($last['params'][1]) && $last['params'][1] === 1) {
            $tagIdBoundAsInt = true;
        }
        $this->assertTrue(
            $tagIdBoundAsInt,
            'tag_id should be bound as intval-safe value (1), not raw injection string'
        );
    }

    private function mockRegistryConnection(): void
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

    private function mockRegistryForDelRelMemberTag(): void
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

        $mockMemberRelTags = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['deleteBy', 'create', 'getInfo', 'lists'])
            ->getMock();
        $mockMemberRelTags->method('deleteBy')->willReturnCallback(
            function ($data) use ($self) {
                $self->deleteByCallCount++;

                return false;
            }
        );

        $mockDefaultRepo = $this->getMockBuilder(\stdClass::class)->addMethods(['create'])->getMock();
        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturnCallback(
            function ($class) use ($mockMemberRelTags, $mockDefaultRepo) {
                if ($class === MemberRelTags::class) {
                    return $mockMemberRelTags;
                }

                return $mockDefaultRepo;
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
