<?php

declare(strict_types=1);

namespace Tests\GoodsBundle;

use GoodsBundle\Entities\ItemsBarcode;
use GoodsBundle\Services\ItemsService;

/**
 * codex-security-01-platform-baseline T02：ItemsService::saveBarcode 参数化（TC-01-06）
 */
class ItemsServiceSaveBarcodeSqlInjectionTest extends \TestCase
{
    /** @var array<int, array{sql: string, params: array, types: array}> */
    private array $captured = [];

    private bool $executeUpdateCalled = false;

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
        $this->mockRegistryForSaveBarcode();
    }

    /**
     * TC-01-06 / AC-01-06：barcode 注入串 → 参数绑定，无 SQL 字面量拼接
     * #given 恶意 barcode 含 SQL meta 字符
     * #when Reflection 调用 saveBarcode
     * #then SQL 含 ? 占位符；恶意串在 params 中绑定，不在 SQL 字面量
     */
    public function testTc0106MaliciousBarcodeUsesParameterBinding(): void
    {
        #given
        $maliciousBarcode = "x' OR '1'='1";

        $service = new ItemsService();
        $method = new \ReflectionMethod(ItemsService::class, 'saveBarcode');
        $method->setAccessible(true);

        #when
        $method->invoke($service, 100, 200, 1, $maliciousBarcode);

        #then
        $this->assertTrue($this->executeUpdateCalled, 'executeUpdate should be called');
        $this->assertNotEmpty($this->captured);

        $last = end($this->captured);
        $this->assertStringContainsString('?', $last['sql']);
        $this->assertStringNotContainsString("x' OR '1'='1", $last['sql']);
        $this->assertContains($maliciousBarcode, array_values($last['params']));
    }

    private function mockRegistryForSaveBarcode(): void
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

        $mockItemsBarcode = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['deleteBy', 'count'])
            ->getMock();
        $mockItemsBarcode->method('deleteBy')->willReturn(true);
        $mockItemsBarcode->method('count')->willReturn(0);

        $defaultRepo = $this->getMockBuilder(\stdClass::class)->addMethods(['create'])->getMock();

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturnCallback(
            function ($class) use ($mockItemsBarcode, $defaultRepo) {
                if ($class === ItemsBarcode::class) {
                    return $mockItemsBarcode;
                }

                return $defaultRepo;
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
