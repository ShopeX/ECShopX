<?php

declare(strict_types=1);

namespace Tests\PopularizeBundle;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response\Factory;
use Illuminate\Http\Request;
use Mockery;
use PopularizeBundle\Http\Api\V1\Action\BrokerageController;
use SalespersonBundle\Http\FrontApi\V1\Action\ShopSalespersonController;

/**
 * T6a（RED）：Controller 层参数校验 — 非法输入应在进入 Service 前被拒绝。
 * 计划：.tasks/plans/popularize-raw-sql-sqli-fix.md TC-C-01~03
 *
 * 使用 overload mock 隔离 BrokerageService，须独立进程避免与同 suite 其他用例类加载冲突。
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ControllerValidationTest extends \TestCase
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
     * TC-C-01：brokerageCount 传入非整数 distributor_id 应校验失败，不进入 BrokerageService。
     * #given Api brokerageCount，distributor_id 非整数
     * #when validator
     * #then 校验失败，不进 Service
     */
    public function testTcC01BrokerageCountRejectsNonIntegerDistributorId(): void
    {
        $this->bindAuth(1);
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindPromoterCountOverload();

        $brokerageServiceCalled = false;
        $brokerageMock = Mockery::mock('overload:PopularizeBundle\Services\BrokerageService');
        $brokerageMock->shouldReceive('getSalesmanBrokerageCount')->andReturnUsing(
            function () use (&$brokerageServiceCalled): array {
                $brokerageServiceCalled = true;

                return [];
            }
        );

        $request = Request::create('/popularize/brokerage/count', 'GET', [
            'distributor_id' => '1abc',
        ]);

        $controller = new BrokerageController();

        try {
            $controller->brokerageCount($request);
            $this->fail('Expected ResourceException when distributor_id is not an integer');
        } catch (ResourceException $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertFalse($brokerageServiceCalled, 'BrokerageService must not be called after controller validation failure');
        }
    }

    /**
     * TC-C-02：getBrokerageList 传入非整数 user_id 应校验失败，不进入 BrokerageService。
     * #given Api getBrokerageList，user_id 非整数
     * #when validator
     * #then 校验失败
     */
    public function testTcC02GetBrokerageListRejectsNonIntegerUserId(): void
    {
        $this->bindAuth(1);
        $this->bindResponseFactory();
        $this->bindLog();

        $brokerageServiceCalled = false;
        $brokerageMock = Mockery::mock('overload:PopularizeBundle\Services\BrokerageService');
        $brokerageMock->shouldReceive('getBrokerageDbList')->andReturnUsing(
            function () use (&$brokerageServiceCalled): array {
                $brokerageServiceCalled = true;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $brokerageMock->shouldReceive('getSalesmanBrokeragelistsBySql')->never();

        $request = Request::create('/popularize/brokerage/logs', 'GET', [
            'page' => 1,
            'pageSize' => 10,
            'user_id' => 'abc',
        ]);

        $controller = new BrokerageController();

        try {
            $controller->getBrokerageList($request);
            $this->fail('Expected ResourceException when user_id is not an integer');
        } catch (ResourceException $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertFalse($brokerageServiceCalled, 'BrokerageService must not be called after controller validation failure');
        }
    }

    /**
     * TC-C-03：brokagestaticlist 传入非法 groupby 应在 Controller 层拒绝，不进入 BrokerageService。
     * #given Front/Salesperson brokagestaticlist，groupby=evil
     * #when validator 或 Service
     * #then 拒绝（Controller 层：Service 不应被调用）
     */
    public function testTcC03BrokageStaticListRejectsInvalidGroupbyBeforeService(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();

        $brokerageServiceCalled = false;
        $brokerageMock = Mockery::mock('overload:PopularizeBundle\Services\BrokerageService');
        $brokerageMock->shouldReceive('getSalesmanBrokerageCountList')->andReturnUsing(
            function () use (&$brokerageServiceCalled): array {
                $brokerageServiceCalled = true;

                return [];
            }
        );

        $request = Request::create('/h5app/wxapp/salespersonadmin/brokagestaticlist', 'GET', [
            'groupby' => 'evil',
            'page' => 1,
            'pageSize' => 10,
            'distributor_id' => 1,
        ]);
        $request->merge([
            'auth' => [
                'company_id' => 1,
                'user_id' => 9,
            ],
        ]);

        $controller = new ShopSalespersonController();

        try {
            $controller->brokagestaticlist($request);
            $this->fail('Expected ResourceException when groupby is invalid');
        } catch (ResourceException $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertFalse($brokerageServiceCalled, 'BrokerageService must not be called after controller validation failure');
        }
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

    private function bindLog(): void
    {
        $log = $this->getMockBuilder(\stdClass::class)->addMethods(['debug', 'info'])->getMock();
        $log->method('debug')->willReturn(null);
        $log->method('info')->willReturn(null);
        $this->app->instance('log', $log);
    }

    private function bindPromoterCountOverload(): void
    {
        $promoterMock = Mockery::mock('overload:PopularizeBundle\Services\PromoterCountService');
        $promoterMock->shouldReceive('getCount')->andReturn([
            'payedRebate' => 0,
            'itemTotalPrice' => 0,
            'cashWithdrawalRebate' => 0,
            'noCloseRebate' => 0,
            'rebateTotal' => 0,
            'freezeCashWithdrawalRebate' => 0,
            'pointTotal' => 0,
        ]);
        $promoterMock->shouldReceive('getPromoterCount')->andReturn([
            'payedRebate' => 0,
            'itemTotalPrice' => 0,
            'cashWithdrawalRebate' => 0,
            'noCloseRebate' => 0,
            'rebateTotal' => 0,
            'freezeCashWithdrawalRebate' => 0,
            'pointTotal' => 0,
        ]);
    }
}
