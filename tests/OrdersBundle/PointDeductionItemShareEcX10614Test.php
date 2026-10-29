<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use Mockery;
use OrdersBundle\Interfaces\OrderInterface;
use OrdersBundle\Services\OrderService;

/**
 * ECX-10614：积分抵扣行分摊与整单不变量。
 *
 * 样例来自生产单 5407893000230261（优惠后三行 16200/13000/22000 元，运费 2 元，积分抵 51201）。
 * 尾差落最后一行；验收只要求行合计对得上整单。
 */
class PointDeductionItemShareEcX10614Test extends \TestCase
{
    private const COMPANY_ID = 1;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testFullGoodsPointDeductionLeavesZeroItemCashOnAllLines(): void
    {
        $this->mockOpenPointRuleWithFreightDeduct();

        $orderData = [
            'point_use' => 51201,
            'total_fee' => 5120200,
            'freight_fee' => 200,
            'items' => $this->ecx10614ThreeItems(),
        ];

        $result = $this->runPointDeduction($orderData);

        $itemCash = array_map(static function (array $item): int {
            return (int) $item['total_fee'];
        }, $result['items']);

        $this->assertSame([0, 0, 0], $itemCash, '商品全额抵扣后三行总支付价均应为 0');
        $this->assertSame(5120000, (int) array_sum(array_column($result['items'], 'point_fee')));
        $this->assertSame(100, (int) $result['freight_fee']);
        $this->assertSame(100, (int) $result['freight_point_fee']);
        $this->assertSame(5120100, (int) $result['point_fee']);
        $this->assertSame(100, (int) $result['total_fee']);
        $this->assertOrderLineInvariants($result);
    }

    /**
     * 部分积分抵扣：商品侧只抵一半，运费仍全现金；行合计须对得上整单。
     */
    public function testPartialGoodsPointDeductionKeepsOrderLineInvariants(): void
    {
        $this->mockOpenPointRuleWithFreightDeduct();

        $orderData = [
            'point_use' => 25600,
            'total_fee' => 5120200,
            'freight_fee' => 200,
            'items' => $this->ecx10614ThreeItems(),
        ];

        $result = $this->runPointDeduction($orderData);

        $this->assertSame(2560000, (int) $result['point_fee']);
        $this->assertSame(2560000, (int) array_sum(array_column($result['items'], 'point_fee')));
        $this->assertSame(200, (int) $result['freight_fee'], '积分未覆盖运费时现金运费应保持 2 元');
        $this->assertSame(0, (int) ($result['freight_point_fee'] ?? 0));
        $this->assertSame(2560200, (int) $result['total_fee']);
        $this->assertOrderLineInvariants($result);
    }

    /**
     * 单商品行 + 运费混合抵扣：无多行分摊时，商品行应为 0，运费积分 1 + 现金 1。
     */
    public function testSingleItemFullDeductionWithMixedFreightKeepsInvariants(): void
    {
        $this->mockOpenPointRuleWithFreightDeduct();

        $orderData = [
            'point_use' => 101,
            'total_fee' => 10200,
            'freight_fee' => 200,
            'items' => [
                [
                    'item_id' => 9001,
                    'item_name' => '单SKU全额抵扣',
                    'price' => 10000,
                    'num' => 1,
                    'total_fee' => 10000,
                ],
            ],
        ];

        $result = $this->runPointDeduction($orderData);

        $this->assertCount(1, $result['items']);
        $this->assertSame(0, (int) $result['items'][0]['total_fee']);
        $this->assertSame(10000, (int) $result['items'][0]['point_fee']);
        $this->assertSame(100, (int) $result['freight_fee']);
        $this->assertSame(100, (int) $result['freight_point_fee']);
        $this->assertSame(10100, (int) $result['point_fee']);
        $this->assertSame(100, (int) $result['total_fee']);
        $this->assertOrderLineInvariants($result);
    }

    /**
     * @param array<string,mixed> $orderData
     * @return array<string,mixed>
     */
    private function runPointDeduction(array $orderData): array
    {
        $service = new OrderService(Mockery::mock(OrderInterface::class));

        return $service->getPointDeduction(self::COMPANY_ID, $orderData, 100000);
    }

    /**
     * @param array<string,mixed> $result
     */
    private function assertOrderLineInvariants(array $result): void
    {
        $this->assertSame(
            (int) $result['total_fee'],
            (int) array_sum(array_column($result['items'], 'total_fee')) + (int) $result['freight_fee'],
            'Σ行实付 + 现金运费 应等于整单实付'
        );
        $this->assertSame(
            (int) $result['point_fee'],
            (int) array_sum(array_column($result['items'], 'point_fee')) + (int) ($result['freight_point_fee'] ?? 0),
            'Σ行积分抵扣 + 运费积分抵扣 应等于整单积分抵扣'
        );
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function ecx10614ThreeItems(): array
    {
        return [
            [
                'item_id' => 6734,
                'item_name' => '冰川护盾户外滑雪服',
                'price' => 850000,
                'num' => 2,
                'total_fee' => 1620000,
            ],
            [
                'item_id' => 6764,
                'item_name' => 'Y硬壳冲锋衣',
                'price' => 980000,
                'num' => 2,
                'total_fee' => 1300000,
            ],
            [
                'item_id' => 6749,
                'item_name' => '新款粉色羽绒服',
                'price' => 1100000,
                'num' => 2,
                'total_fee' => 2200000,
            ],
        ];
    }

    private function mockOpenPointRuleWithFreightDeduct(): void
    {
        $this->mockPointRule([
            'isOpenMemberPoint' => 'true',
            'isOpenDeductPoint' => 'true',
            'deduct_proportion_limit' => 100,
            'deduct_point' => 1,
            'can_deduct_freight' => 1,
        ]);
    }

    /**
     * @param array<string,mixed> $rule
     */
    private function mockPointRule(array $rule): void
    {
        $payload = json_encode($rule);
        $mockConnection = Mockery::mock();
        $mockConnection->shouldReceive('get')->andReturn($payload);

        $mockRedis = Mockery::mock();
        $mockRedis->shouldReceive('connection')->with('default')->andReturn($mockConnection);

        $this->app->instance('redis', $mockRedis);
    }
}
