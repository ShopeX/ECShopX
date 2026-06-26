<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use AftersalesBundle\Repositories\AftersalesDetailRepository;
use AftersalesBundle\Repositories\AftersalesRefundRepository;
use DistributionBundle\Repositories\DistributorRepository;
use GoodsBundle\Repositories\ItemsRepository;
use Illuminate\Support\Facades\Log;
use OrdersBundle\Repositories\NormalOrdersItemsRepository;
use OrdersBundle\Repositories\NormalOrdersRepository;

/**
 * 从 {@see aftersales_refund} + 订单/明细组装 {@see shuyun.base.refund.sync} 对象列表（不含 Header `platform`）。
 */
class ShuyunOpenPlatformNormalOrderRefundPayloadAssembler
{
    public const LOG_CHANNEL = 'shuyun_open_platform';

    private AftersalesRefundRepository $aftersalesRefundRepository;

    private AftersalesDetailRepository $aftersalesDetailRepository;

    private NormalOrdersRepository $normalOrdersRepository;

    private NormalOrdersItemsRepository $normalOrdersItemsRepository;

    private ItemsRepository $itemsRepository;

    private DistributorRepository $distributorRepository;

    private ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver;

    public function __construct(
        AftersalesRefundRepository $aftersalesRefundRepository,
        AftersalesDetailRepository $aftersalesDetailRepository,
        NormalOrdersRepository $normalOrdersRepository,
        NormalOrdersItemsRepository $normalOrdersItemsRepository,
        ItemsRepository $itemsRepository,
        DistributorRepository $distributorRepository,
        ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver
    ) {
        $this->aftersalesRefundRepository = $aftersalesRefundRepository;
        $this->aftersalesDetailRepository = $aftersalesDetailRepository;
        $this->normalOrdersRepository = $normalOrdersRepository;
        $this->normalOrdersItemsRepository = $normalOrdersItemsRepository;
        $this->itemsRepository = $itemsRepository;
        $this->distributorRepository = $distributorRepository;
        $this->shopIdResolver = $shopIdResolver;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildRefundPayloads(int $companyId, string $refundBn): array
    {
        if ($companyId < 1 || $refundBn === '') {
            return [];
        }

        $refund = $this->aftersalesRefundRepository->getInfo([
            'company_id' => $companyId,
            'refund_bn' => $refundBn,
        ]);
        if ($refund === []) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun refund.sync payload: refund not found.', [
                'company_id' => $companyId,
                'refund_bn' => $refundBn,
            ]);

            return [];
        }

        $orderId = trim((string) ($refund['order_id'] ?? ''));
        if ($orderId === '') {
            return [];
        }

        $order = $this->normalOrdersRepository->getInfo([
            'company_id' => $companyId,
            'order_id' => $orderId,
        ]);
        if ($order === []) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun refund.sync payload: order not found.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return [];
        }

        $distributorRow = $this->resolveDistributorRow($companyId, (int) ($order['distributor_id'] ?? 0));
        if ($distributorRow === null) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun refund.sync payload: distributor not found.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return [];
        }

        try {
            $shopId = $this->shopIdResolver->resolve($distributorRow);
        } catch (\InvalidArgumentException $e) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun refund.sync payload: invalid distributor for shop_id.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return [];
        }

        $items = $this->normalOrdersItemsRepository->get($companyId, $orderId);
        if ($items === []) {
            return [];
        }

        $supplierId = (int) ($refund['supplier_id'] ?? 0);
        $filtered = [];
        foreach ($items as $line) {
            if ((int) ($line['supplier_id'] ?? 0) !== $supplierId) {
                continue;
            }
            $filtered[] = $line;
        }
        if ($filtered === []) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun refund.sync payload: no items for refund supplier.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'supplier_id' => $supplierId,
            ]);

            return [];
        }

        $byLineId = [];
        foreach ($filtered as $line) {
            $lid = (int) ($line['id'] ?? 0);
            if ($lid > 0) {
                $byLineId[$lid] = $line;
            }
        }

        $aftersalesBn = trim((string) ($refund['aftersales_bn'] ?? ''));
        if ($aftersalesBn !== '') {
            return $this->buildFromAftersalesDetails($refund, $order, $shopId, $companyId, $aftersalesBn, $byLineId);
        }

        return $this->buildFromCancelStyleRefund($refund, $order, $shopId, $byLineId);
    }

    /**
     * @param  array<string, mixed>  $refund
     * @param  array<string, mixed>  $order
     * @param  array<int, array<string, mixed>>  $byLineId
     * @return list<array<string, mixed>>
     */
    private function buildFromAftersalesDetails(
        array $refund,
        array $order,
        string $shopId,
        int $companyId,
        string $aftersalesBn,
        array $byLineId
    ): array {
        $list = $this->aftersalesDetailRepository->getList([
            'company_id' => $companyId,
            'aftersales_bn' => $aftersalesBn,
        ], 0, -1, ['detail_id' => 'ASC']);
        $rows = $list['list'] ?? [];
        if ($rows === []) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun refund.sync payload: aftersales_detail empty.', [
                'company_id' => $companyId,
                'aftersales_bn' => $aftersalesBn,
            ]);

            return [];
        }

        $refundBn = (string) ($refund['refund_bn'] ?? '');
        $phase = ShuyunOpenPlatformNormalOrderRefundStatusMapper::resolveRefundPhase($order, $refund['refund_type'] ?? '0');
        $status = ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapRefundStatus($refund);
        $created = ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime((int) ($refund['create_time'] ?? 0));
        $modified = ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime((int) ($refund['update_time'] ?? 0) > 0
            ? (int) $refund['update_time']
            : (int) ($refund['create_time'] ?? 0));
        $reason = trim((string) ($refund['refunds_memo'] ?? ''));
        if ($reason === '') {
            $reason = '售后退款';
        }

        $itemIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['item_id'] ?? 0),
            $byLineId
        ))));
        $productIdByItemId = $this->loadItemProductIdMap($companyId, $itemIds);

        /** @var list<array{detail: array<string, mixed>, line: array<string, mixed>, lineId: int, lineItemId: int, productId: string}> $candidates */
        $candidates = [];
        foreach ($rows as $detail) {
            $subId = (int) ($detail['sub_order_id'] ?? 0);
            $line = $byLineId[$subId] ?? null;
            if (! is_array($line)) {
                continue;
            }
            $lineItemId = (int) ($line['item_id'] ?? 0);
            $lineId = (int) ($line['id'] ?? 0);
            if ($lineItemId <= 0 || $lineId <= 0) {
                continue;
            }
            $candidates[] = [
                'detail' => $detail,
                'line' => $line,
                'lineId' => $lineId,
                'lineItemId' => $lineItemId,
                'productId' => $productIdByItemId[$lineItemId]
                    ?? ShuyunOpenPlatformItemProductIdResolver::resolve(0, 0, $lineItemId),
            ];
        }
        if ($candidates === []) {
            return [];
        }

        /** @var array<int, int> $weights */
        $weights = [];
        foreach ($candidates as $idx => $c) {
            $weights[$idx] = max(0, (int) ($c['detail']['refund_fee'] ?? 0));
        }
        if (array_sum($weights) === 0) {
            foreach ($candidates as $idx => $c) {
                $weights[$idx] = max(0, (int) ($c['line']['total_fee'] ?? 0));
            }
        }

        $totalRefundedFen = $this->resolveRefundAmountFenForSync($refund);
        $allocated = ShuyunOpenPlatformRefundLineFeeAllocator::allocateProportional($totalRefundedFen, $weights);

        $out = [];
        foreach ($candidates as $idx => $c) {
            $detail = $c['detail'];
            $lineId = $c['lineId'];
            $lineItemId = $c['lineItemId'];
            $productId = $c['productId'];
            $detailId = (int) ($detail['detail_id'] ?? 0);
            $rid = $detailId > 0 ? $refundBn.'_d'.$detailId : $refundBn.'_l'.$lineId;
            $feeFen = (int) ($allocated[$idx] ?? 0);
            $atype = (string) ($detail['aftersales_type'] ?? 'ONLY_REFUND');
            $goodReturn = ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapGoodReturnFromAftersalesDetailType($atype);

            $out[] = [
                'refund_id' => $rid,
                'order_id' => (string) ($order['order_id'] ?? ''),
                'order_item_id' => (string) $lineId,
                'shop_id' => $shopId,
                'product_id' => (string) $productId,
                'sku_id' => (string) $lineItemId,
                'refund_fee' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber($feeFen),
                'refund_status' => $status,
                'good_return' => $goodReturn,
                'refund_reason' => $reason,
                'created' => $created,
                'modified' => $modified,
                'refund_phase' => $phase,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $refund
     * @param  array<string, mixed>  $order
     * @param  array<int, array<string, mixed>>  $byLineId
     * @return list<array<string, mixed>>
     */
    private function buildFromCancelStyleRefund(
        array $refund,
        array $order,
        string $shopId,
        array $byLineId
    ): array {
        $refundBn = (string) ($refund['refund_bn'] ?? '');
        $weights = [];
        foreach ($byLineId as $lid => $line) {
            $weights[$lid] = (int) ($line['total_fee'] ?? 0);
        }
        $totalRefundFen = $this->resolveRefundAmountFenForSync($refund);
        $allocated = ShuyunOpenPlatformRefundLineFeeAllocator::allocateProportional($totalRefundFen, $weights);

        $phase = ShuyunOpenPlatformNormalOrderRefundStatusMapper::resolveRefundPhase($order, $refund['refund_type'] ?? '0');
        $status = ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapRefundStatus($refund);
        $created = ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime((int) ($refund['create_time'] ?? 0));
        $modified = ShuyunOpenPlatformNormalOrderShuyunTradeMapper::formatDateTime((int) ($refund['update_time'] ?? 0) > 0
            ? (int) $refund['update_time']
            : (int) ($refund['create_time'] ?? 0));
        $reason = trim((string) ($refund['refunds_memo'] ?? ''));
        if ($reason === '') {
            $reason = '订单取消退款';
        }

        $companyId = (int) ($refund['company_id'] ?? 0);
        $itemIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['item_id'] ?? 0),
            $byLineId
        ))));
        $productIdByItemId = $this->loadItemProductIdMap($companyId, $itemIds);

        $out = [];
        foreach ($byLineId as $lid => $line) {
            $feeFen = (int) ($allocated[$lid] ?? 0);
            $lineItemId = (int) ($line['item_id'] ?? 0);
            if ($lineItemId <= 0) {
                continue;
            }
            $productId = $productIdByItemId[$lineItemId]
                ?? ShuyunOpenPlatformItemProductIdResolver::resolve(0, 0, $lineItemId);
            $out[] = [
                'refund_id' => $refundBn.'_l'.$lid,
                'order_id' => (string) ($order['order_id'] ?? ''),
                'order_item_id' => (string) $lid,
                'shop_id' => $shopId,
                'product_id' => (string) $productId,
                'sku_id' => (string) $lineItemId,
                'refund_fee' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber($feeFen),
                'refund_status' => $status,
                'good_return' => 'SY_ONLY_FEE',
                'refund_reason' => $reason,
                'created' => $created,
                'modified' => $modified,
                'refund_phase' => $phase,
            ];
        }

        return $out;
    }

    /**
     * 数云 refund.sync 主金额：优先主退款单实退 `refunded_fee`（分）；为 0 时用申请额 `refund_fee` 兜底（兼容未回写）。
     */
    private function resolveRefundAmountFenForSync(array $refund): int
    {
        $refunded = (int) ($refund['refunded_fee'] ?? 0);
        if ($refunded > 0) {
            return $refunded;
        }

        return max(0, (int) ($refund['refund_fee'] ?? 0));
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
}
