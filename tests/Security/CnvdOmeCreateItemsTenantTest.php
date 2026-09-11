<?php

declare(strict_types=1);

namespace Tests\Security;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response\Factory;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use Mockery;
use SystemLinkBundle\Http\ThirdApi\V1\Action\Item;
use TestCase;

/**
 * CNVD ome createItems 租户绑定 — TC-TENANT-01～05
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CnvdOmeCreateItemsTenantTest extends TestCase
{
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();
        $app->instance('path.lang', $app->basePath('resources/lang'));
        $app->register(\Illuminate\Translation\TranslationServiceProvider::class);
        $app->register(\Illuminate\Validation\ValidationServiceProvider::class);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['common.system_companys_id' => 1]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-TENANT-01 / AC-TENANT-01：company_id 与 system_companys_id 一致（含数字字符串）通过。
     * #given system_companys_id=1，请求 company_id 为字符串 "1"
     * #when createItems
     * #then 允许继续，addItems 使用规范化 company_id=1
     */
    public function testTcTenant01MatchingCompanyIdPassesWithNumericString(): void
    {
        #given
        $this->bindResponseFactory();
        $capturedParams = null;
        $itemsMock = Mockery::mock('overload:'.ItemsService::class);
        $itemsMock->shouldReceive('addItems')->once()->andReturnUsing(
            function (array $params) use (&$capturedParams) {
                $capturedParams = $params;

                return true;
            }
        );

        $request = Request::create('/systemlink/ome/createitems', 'POST', array_merge(
            $this->minimalValidItemParams(),
            ['company_id' => '1']
        ));

        #when
        $action = new Item();
        $result = $action->createItems($request);

        #then
        $this->assertSame(['status' => true], $result);
        $this->assertIsArray($capturedParams);
        $this->assertSame(1, $capturedParams['company_id']);
    }

    private function bindResponseFactory(): void
    {
        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('array')->andReturnUsing(static fn (array $payload): array => $payload);
        $this->app->instance(Factory::class, $factory);
    }

    /**
     * TC-TENANT-02 / AC-TENANT-02：不一致 company_id 拒绝，不得写库。
     * #given system_companys_id=1，请求 company_id=2
     * #when createItems
     * #then ResourceException，addItems 未被调用
     */
    public function testTcTenant02MismatchedCompanyIdRejected(): void
    {
        #given
        $this->bindResponseFactory();
        $addItemsCalled = false;
        $itemsMock = Mockery::mock('overload:'.ItemsService::class);
        $itemsMock->shouldReceive('addItems')->andReturnUsing(
            function () use (&$addItemsCalled) {
                $addItemsCalled = true;

                return true;
            }
        );

        $request = Request::create('/systemlink/ome/createitems', 'POST', array_merge(
            $this->minimalValidItemParams(),
            ['company_id' => 2]
        ));

        #when / #then
        try {
            (new Item())->createItems($request);
            $this->fail('Expected ResourceException for mismatched company_id');
        } catch (ResourceException $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertFalse($addItemsCalled, 'addItems must not be called for foreign company_id');
        }
    }

    /**
     * TC-TENANT-03 / AC-TENANT-03：省略/空/null company_id 覆盖为 system_companys_id。
     * #given system_companys_id=1，company_id 缺省、空串或 null
     * #when createItems
     * #then addItems 收到 company_id=1
     */
    public function testTcTenant03MissingEmptyNullCompanyIdOverridden(): void
    {
        #given
        $this->bindResponseFactory();
        $captured = [];
        $itemsMock = Mockery::mock('overload:'.ItemsService::class);
        $itemsMock->shouldReceive('addItems')->andReturnUsing(
            function (array $params) use (&$captured) {
                $captured[] = $params['company_id'] ?? null;

                return true;
            }
        );

        $cases = [
            'omitted' => $this->minimalValidItemParams(),
            'empty' => array_merge($this->minimalValidItemParams(), ['company_id' => '']),
            'null' => array_merge($this->minimalValidItemParams(), ['company_id' => null]),
        ];

        #when
        foreach ($cases as $payload) {
            $request = Request::create('/systemlink/ome/createitems', 'POST', $payload);
            (new Item())->createItems($request);
        }

        #then
        $this->assertSame([1, 1, 1], $captured, 'omitted/empty/null company_id must be overridden to system_companys_id');
    }

    /**
     * TC-TENANT-04 / AC-TENANT-04：company_id 为 0 或非数字字符串时拒绝。
     * #given system_companys_id=1，company_id=0 或 "abc"
     * #when createItems
     * #then ResourceException，addItems 未被调用
     */
    public function testTcTenant04InvalidCompanyIdRejected(): void
    {
        #given
        $this->bindResponseFactory();
        $addItemsCalled = false;
        $itemsMock = Mockery::mock('overload:'.ItemsService::class);
        $itemsMock->shouldReceive('addItems')->andReturnUsing(
            function () use (&$addItemsCalled) {
                $addItemsCalled = true;

                return true;
            }
        );

        foreach ([0, 'abc'] as $invalidCompanyId) {
            $addItemsCalled = false;
            $request = Request::create('/systemlink/ome/createitems', 'POST', array_merge(
                $this->minimalValidItemParams(),
                ['company_id' => $invalidCompanyId]
            ));

            #when / #then
            try {
                (new Item())->createItems($request);
                $this->fail('Expected ResourceException for invalid company_id: '.var_export($invalidCompanyId, true));
            } catch (ResourceException $e) {
                $this->assertNotEmpty($e->getMessage());
                $this->assertFalse($addItemsCalled, 'addItems must not be called for invalid company_id');
            }
        }
    }

    /**
     * TC-TENANT-05 / AC-TENANT-05：ome.items.create 与直连共用 createItems 租户绑定。
     * #given Verify 映射与 createItems 源码
     * #when 检查租户绑定落点
     * #then Verify 指向 Item@createItems，且 createItems 绑定 system_companys_id
     */
    public function testTcTenant05VerifyRouteSharesCreateItemsTenantBinding(): void
    {
        #given
        $verifySource = (string) file_get_contents(
            dirname(__DIR__, 2).'/src/SystemLinkBundle/Http/ThirdApi/V1/Action/Verify.php'
        );
        $createItemsBody = $this->methodBody(Item::class, 'createItems');

        #when / #then
        $this->assertStringContainsString("'ome.items.create' => 'Item@createItems'", $verifySource);
        $this->assertStringContainsString("config('common.system_companys_id')", $createItemsBody);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalValidItemParams(): array
    {
        return [
            'item_name' => '租户测试商品',
            'pics' => 'https://example.com/item.jpg',
            'sort' => 1,
        ];
    }
}
