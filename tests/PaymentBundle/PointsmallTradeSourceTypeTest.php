<?php

declare(strict_types=1);

namespace Tests\PaymentBundle;

use OrdersBundle\Traits\GetOrderServiceTrait;
use PaymentBundle\Services\PaymentService;

class PointsmallTradeSourceTypeTest extends \TestCase
{
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRegistryForGetOrderService();
    }

    /**
     * TC-01：入参已是 normal_pointsmall 时不应重复拼接 _pointsmall。
     */
    public function testTc01NormalPointsmallDoesNotDoubleAppendPointsmall(): void
    {
        #given order_type 已是 normal_pointsmall，order_class 为 pointsmall
        $orderType = 'normal_pointsmall';
        $orderClass = 'pointsmall';

        #when 解析 trade_source_type
        $tradeSourceType = PaymentService::resolveTradeSourceType($orderType, $orderClass);

        #then 结果应为 normal_pointsmall，不得出现 normal_pointsmall_pointsmall
        $this->assertSame('normal_pointsmall', $tradeSourceType);
        $this->assertNotSame('normal_pointsmall_pointsmall', $tradeSourceType);
    }

    /**
     * TC-02：normal + class pointsmall 应拼接为 normal_pointsmall。
     */
    public function testTc02NormalWithPointsmallClassResolvesToNormalPointsmall(): void
    {
        #given order_type 为 normal，order_class 为 pointsmall
        $orderType = 'normal';
        $orderClass = 'pointsmall';

        #when 解析 trade_source_type
        $tradeSourceType = PaymentService::resolveTradeSourceType($orderType, $orderClass);

        #then 结果应为 normal_pointsmall
        $this->assertSame('normal_pointsmall', $tradeSourceType);
    }

    /**
     * TC-03：解析结果必须是 GetOrderServiceTrait 已支持的 trade_source_type。
     */
    public function testTc03ResolvedTradeSourceTypeIsSupportedByGetOrderServiceTrait(): void
    {
        $cases = [
            ['orderType' => 'normal_pointsmall', 'orderClass' => 'pointsmall'],
            ['orderType' => 'normal', 'orderClass' => 'pointsmall'],
        ];

        foreach ($cases as $case) {
            #given 积分商城相关 order_type / order_class 组合
            #when 解析 trade_source_type
            $tradeSourceType = PaymentService::resolveTradeSourceType(
                $case['orderType'],
                $case['orderClass']
            );

            #then getOrderService 必须能识别该类型（至少含 normal_pointsmall）
            $this->assertSupportedTradeSourceType($tradeSourceType);
        }
    }

    private function assertSupportedTradeSourceType(string $tradeSourceType): void
    {
        $probe = new class {
            use GetOrderServiceTrait;

            public function probe(string $type): void
            {
                $this->getOrderService($type);
            }
        };

        $probe->probe($tradeSourceType);
        $this->addToAssertionCount(1);
    }

    private function mockRegistryForGetOrderService(): void
    {
        $mockRepo = $this->getMockBuilder(\stdClass::class)->addMethods(['create'])->getMock();
        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturn($mockRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager'])
            ->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }
}
