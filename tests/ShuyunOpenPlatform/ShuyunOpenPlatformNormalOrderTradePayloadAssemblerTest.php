<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use DistributionBundle\Repositories\DistributorRepository;
use GoodsBundle\Repositories\ItemsRepository;
use OrdersBundle\Repositories\NormalOrdersItemsRepository;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Repositories\TradeRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformNormalOrderTradePayloadAssembler;

/** @see .tasks/plans/shuyun-shop-id-platform-migration.md TC-SID-06 */
class ShuyunOpenPlatformNormalOrderTradePayloadAssemblerTest extends \TestCase
{
    public function testBuildOneOrderPayloadUsesOffSuffixShopId(): void
    {
        config(['shuyun_open_platform.offline_plat_id_suffix' => '-off']);

        $orders = $this->createMock(NormalOrdersRepository::class);
        $orders->method('getInfo')->with([
            'company_id' => 1,
            'order_id' => 'ORD-1',
        ])->willReturn([
            'company_id' => 1,
            'order_id' => 'ORD-1',
            'user_id' => 100,
            'distributor_id' => 42,
            'total_fee' => 1000,
            'create_time' => time(),
            'order_status' => 'PAYED',
        ]);

        $items = $this->createMock(NormalOrdersItemsRepository::class);
        $items->method('get')->willReturn([
            ['id' => 501, 'item_id' => 1, 'num' => 1, 'price' => 1000, 'total_fee' => 1000, 'item_name' => '商品A'],
        ]);

        $trade = $this->createMock(TradeRepository::class);
        $trade->method('lists')->willReturn(['list' => []]);

        $goods = $this->createMock(ItemsRepository::class);
        $goods->method('getLists')->willReturn(['list' => [
            ['item_id' => 1, 'goods_id' => 5000, 'default_item_id' => 10],
        ]]);

        $distributors = $this->createMock(DistributorRepository::class);
        $distributors->method('getInfo')->with([
            'company_id' => 1,
            'distributor_id' => 42,
        ])->willReturn(['distributor_id' => 42, 'name' => '店42']);

        $assembler = new ShuyunOpenPlatformNormalOrderTradePayloadAssembler(
            $orders,
            $items,
            $trade,
            $goods,
            $distributors,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
        );

        $payload = $assembler->buildOneOrderPayload(1, 'ORD-1', '11');
        $this->assertIsArray($payload);
        $this->assertSame('42-off', $payload['shop_id'] ?? null);
        $this->assertSame('5000', $payload['orders'][0]['product_id'] ?? null);
    }
}
