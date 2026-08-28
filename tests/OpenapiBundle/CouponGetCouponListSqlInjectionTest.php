<?php

declare(strict_types=1);

namespace Tests\OpenapiBundle;

use Illuminate\Http\Request;
use OpenapiBundle\Exceptions\ApiResponseException;
use OpenapiBundle\Http\ThirdApi\V1\Action\Coupon;

/**
 * codex-security-01-platform-baseline T02：Coupon::getCouponList 参数化（TC-01-07）
 */
class CouponGetCouponListSqlInjectionTest extends \TestCase
{
    /** @var array<int, array{sql: string, params: array, types: array}> */
    private array $captured = [];

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
        $this->mockRegistryConnection();
    }

    /**
     * TC-01-07 / AC-01-06：Coupon distributor_id 拼接 → 参数绑定
     * #given 恶意 distributor_id 含 OR 注入
     * #when 调用 getCouponList
     * #then SQL 含 ? 占位符；恶意串在 params 绑定，不在 SQL 字面量
     */
    public function testTc0107MaliciousDistributorIdUsesParameterBinding(): void
    {
        #given
        $maliciousDistributorId = "1' OR '1'='1";
        $request = Request::create('/', 'GET', [
            'auth' => ['company_id' => 5],
            'distributor_id' => $maliciousDistributorId,
        ]);

        $coupon = $this->getMockBuilder(Coupon::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['api_response'])
            ->getMock();
        $coupon->method('api_response')->willReturn(null);

        #when
        try {
            $coupon->getCouponList($request);
        } catch (ApiResponseException $e) {
            // expected success path ends with api_response throw
        }

        #then
        $this->assertNotEmpty($this->captured);
        $last = end($this->captured);
        $this->assertStringContainsString('?', $last['sql']);
        $this->assertStringNotContainsString("1' OR '1'='1", $last['sql']);
        $this->assertContains('%,' . $maliciousDistributorId . ',%', array_values($last['params']));
        $this->assertContains(5, array_values($last['params']));
    }

    private function mockRegistryConnection(): void
    {
        $self = $this;
        $conn = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $conn->method('executeQuery')->willReturnCallback(
            function ($sql, $params = [], $types = []) use ($self) {
                $self->captured[] = compact('sql', 'params', 'types');

                return new class() {
                    public function fetchAll(): array
                    {
                        return [];
                    }
                };
            }
        );

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getConnection', 'getManager'])
            ->getMock();
        $mockRegistry->method('getConnection')->with('default')->willReturn($conn);
        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturn(
            $this->getMockBuilder(\stdClass::class)->addMethods(['create'])->getMock()
        );
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }
}
