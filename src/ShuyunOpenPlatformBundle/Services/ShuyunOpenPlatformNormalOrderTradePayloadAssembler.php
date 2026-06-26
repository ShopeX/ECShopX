<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use DistributionBundle\Repositories\DistributorRepository;
use GoodsBundle\Repositories\ItemsRepository;
use Illuminate\Support\Facades\Log;
use OrdersBundle\Repositories\NormalOrdersItemsRepository;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Repositories\TradeRepository;

/**
 * 从 {@see orders_normal_orders} + 明细组装单条 {@see shuyun.base.trade.sync} 订单对象（不含 Header `platform`）。
 */
class ShuyunOpenPlatformNormalOrderTradePayloadAssembler
{
    public const LOG_CHANNEL = 'shuyun_open_platform';

    private NormalOrdersRepository $normalOrdersRepository;

    private NormalOrdersItemsRepository $normalOrdersItemsRepository;

    private TradeRepository $tradeRepository;

    private ItemsRepository $itemsRepository;

    private DistributorRepository $distributorRepository;

    private ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver;

    public function __construct(
        NormalOrdersRepository $normalOrdersRepository,
        NormalOrdersItemsRepository $normalOrdersItemsRepository,
        TradeRepository $tradeRepository,
        ItemsRepository $itemsRepository,
        DistributorRepository $distributorRepository,
        ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver
    ) {
        $this->normalOrdersRepository = $normalOrdersRepository;
        $this->normalOrdersItemsRepository = $normalOrdersItemsRepository;
        $this->tradeRepository = $tradeRepository;
        $this->itemsRepository = $itemsRepository;
        $this->distributorRepository = $distributorRepository;
        $this->shopIdResolver = $shopIdResolver;
    }

    /**
     * @return array<string, mixed>|null  单条 trade 对象；不可组装时返回 null（已打日志）
     */
    public function buildOneOrderPayload(int $companyId, string $orderId, string $tradeSource): ?array
    {
        if ($companyId < 1 || $orderId === '' || $tradeSource === '') {
            return null;
        }

        $order = $this->normalOrdersRepository->getInfo([
            'company_id' => $companyId,
            'order_id' => $orderId,
        ]);
        if ($order === []) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun trade.sync payload: order not found.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return null;
        }

        $items = $this->normalOrdersItemsRepository->get($companyId, $orderId);
        if ($items === []) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun trade.sync payload: no order items.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return null;
        }

        $distributorRow = $this->resolveDistributorRow($companyId, (int) ($order['distributor_id'] ?? 0));
        if ($distributorRow === null) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun trade.sync payload: distributor not found.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'distributor_id' => (int) ($order['distributor_id'] ?? 0),
            ]);

            return null;
        }

        try {
            $shopId = $this->shopIdResolver->resolve($distributorRow);
        } catch (\InvalidArgumentException $e) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun trade.sync payload: invalid distributor_id for shop_id.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return null;
        }

        $itemIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['item_id'] ?? 0),
            $items
        ))));
        $productIdByItemId = $this->loadItemProductIdMap($companyId, $itemIds);

        $tradeRow = $this->resolveLatestTradeRow($companyId, $orderId);
        $payTime = $this->resolvePayTimeYmdHis($tradeRow, (int) ($order['create_time'] ?? 0));

        $shuyunStatus = ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus($order);
        $deliveryType = ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapDeliveryType($order);
        $totalFeeFen = (int) ($order['total_fee'] ?? 0);
        $refundSnapshot = $this->loadRefundSnapshot($companyId, $orderId, $totalFeeFen);
        if (($refundSnapshot['is_fully_refunded'] ?? false) === true) {
            $shuyunStatus = 'TRADE_CLOSED_ALL_REFUND';
        }

        $orderLines = [];
        $productNum = 0;
        foreach ($items as $line) {
            $lineItemId = (int) ($line['item_id'] ?? 0);
            $lineId = (int) ($line['id'] ?? 0);
            if ($lineItemId <= 0 || $lineId <= 0) {
                continue;
            }
            $num = (int) ($line['num'] ?? 0);
            if ($num <= 0) {
                continue;
            }
            $productNum += $num;

            $productId = $productIdByItemId[$lineItemId] ?? ShuyunOpenPlatformItemProductIdResolver::resolve(0, 0, $lineItemId);

            $unitPriceFen = (int) ($line['price'] ?? 0);
            $discountFen = (int) ($line['discount_fee'] ?? 0);

            $consignTs = (int) ($line['delivery_time'] ?? 0);
            $orderLines[] = [
                'order_item_id' => (string) $lineId,
                'product_id' => (string) $productId,
                'sku_id' => (string) $lineItemId,
                'product_name' => (string) ($line['item_name'] ?? ''),
                'price' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber($unitPriceFen),
                'product_num' => $num,
                'discount_fee' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber($discountFen),
                'adjust_fee' => 0.0,
                'pay_time' => $payTime,
                'consign_time' => $consignTs > 0
                    ? ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime($consignTs)
                    : null,
                'logistics_company' => (string) ($line['delivery_corp'] ?? ''),
                'logistics_no' => (string) ($line['delivery_code'] ?? ''),
            ];
        }

        if ($orderLines === []) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun trade.sync payload: no valid lines after filter.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return null;
        }

        foreach ($orderLines as $k => $ol) {
            if (($ol['consign_time'] ?? null) === null || $ol['consign_time'] === '') {
                unset($orderLines[$k]['consign_time']);
            }
            if (trim((string) ($ol['logistics_company'] ?? '')) === '') {
                unset($orderLines[$k]['logistics_company']);
            }
            if (trim((string) ($ol['logistics_no'] ?? '')) === '') {
                unset($orderLines[$k]['logistics_no']);
            }
        }
        $orderLines = array_values($orderLines);

        $createdTs = (int) ($order['create_time'] ?? 0);
        $modifiedTs = (int) ($order['update_time'] ?? 0);
        $endTs = (int) ($order['end_time'] ?? 0);

        $freightFen = (int) ($order['freight_fee'] ?? 0);
        $discountOrderFen = (int) ($order['discount_fee'] ?? 0);

        $payload = [
            'shop_id' => $shopId,
            'plat_account' => (string) ($order['user_id'] ?? ''),
            'order_id' => (string) ($order['order_id'] ?? $orderId),
            'order_status' => $shuyunStatus,
            'trade_type' => 'FIXED',
            'is_presale' => '0',
            'trade_source' => $tradeSource,
            'payment' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber($totalFeeFen),
            'post_fee' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber($freightFen),
            'adjust_fee' => 0.0,
            'product_num' => $productNum,
            'created' => ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime($createdTs),
            'modified' => ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime($modifiedTs > 0 ? $modifiedTs : $createdTs),
            'delivery_type' => $deliveryType,
            'trade_discount_fee' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber($discountOrderFen),
            'orders' => $orderLines,
        ];

        if (ShuyunOpenPlatformNormalOrderShuyunTradeMapper::shouldSendEndTime($shuyunStatus) && $endTs > 0) {
            $payload['endtime'] = ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime($endTs);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveDistributorRow(int $companyId, int $distributorId): ?array
    {
        if ($distributorId > 0) {
            $row = $this->distributorRepository->getInfo([
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
            ]);
            if (is_array($row) && $row !== []) {
                return $row;
            }
        }

        $virtual = $this->distributorRepository->getInfo([
            'company_id' => $companyId,
            'distributor_self' => 1,
        ]);

        return is_array($virtual) && $virtual !== [] ? $virtual : null;
    }

    /**
     * @param  list<int>  $itemIds
     * @return array<int, string>  item_id => 数云 product_id
     */
    private function loadItemProductIdMap(int $companyId, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $res = $this->itemsRepository->getLists(
            ['company_id' => $companyId, 'item_id' => $itemIds],
            'item_id, goods_id, default_item_id',
            1,
            -1,
        );
        $out = [];
        foreach (($res['list'] ?? []) as $row) {
            $iid = (int) ($row['item_id'] ?? 0);
            if ($iid > 0) {
                $out[$iid] = ShuyunOpenPlatformItemProductIdResolver::resolveFromItemRow($row);
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveLatestTradeRow(int $companyId, string $orderId): array
    {
        $res = $this->tradeRepository->lists(
            ['company_id' => (string) $companyId, 'order_id' => $orderId],
            [],
            -1,
            1
        );
        $list = $res['list'] ?? [];
        if ($list === []) {
            return [];
        }

        usort($list, static function (array $a, array $b): int {
            return ((int) ($b['time_expire'] ?? 0)) <=> ((int) ($a['time_expire'] ?? 0));
        });

        return $list[0] ?? [];
    }

    /**
     * @param  array<string, mixed>  $tradeRow
     */
    private function resolvePayTimeYmdHis(array $tradeRow, int $fallbackCreateTime): string
    {
        $te = (int) ($tradeRow['time_expire'] ?? 0);
        if ($te > 0) {
            return ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime($te);
        }

        return ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime($fallbackCreateTime);
    }

    /**
     * 仅用于主单全额退款终态（如 `TRADE_CLOSED_ALL_REFUND`）判定；退款明细走 `shuyun.base.refund.sync`，不在 trade.sync 载荷中带退款字段。
     *
     * @return array{is_fully_refunded: bool}
     */
    private function loadRefundSnapshot(int $companyId, string $orderId, int $orderTotalFeeFen): array
    {
        if ($companyId < 1 || $orderId === '' || $orderTotalFeeFen <= 0) {
            return [
                'is_fully_refunded' => false,
            ];
        }

        $conn = app('registry')->getConnection('default');
        $summaryQb = $conn->createQueryBuilder();
        $summary = $summaryQb
            ->select(
                'COALESCE(SUM(CASE WHEN refund_status = \'SUCCESS\' THEN refunded_fee ELSE 0 END), 0) AS sum_refunded_fee'
            )
            ->from('aftersales_refund')
            ->where($summaryQb->expr()->eq('company_id', $summaryQb->expr()->literal($companyId)))
            ->andWhere($summaryQb->expr()->eq('order_id', $summaryQb->expr()->literal($orderId)))
            ->execute()
            ->fetch();

        $successRefundedFeeFen = (int) ($summary['sum_refunded_fee'] ?? 0);

        return [
            'is_fully_refunded' => $successRefundedFeeFen > 0 && $successRefundedFeeFen >= $orderTotalFeeFen,
        ];
    }
}
