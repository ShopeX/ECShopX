<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use Psr\Log\LoggerInterface;

/**
 * 商城 {@see orders_normal_orders.order_class} → 数云 trade.sync {@see trade_source}（整理表字段）。
 * 未配置时 **不入队/不调网关**（由调用方处理），并打 **ERROR** 可检索日志（订单计划 **D-TRADE-SOURCE-UNKNOWN-01** / **A-ORD-TRADE-SRC-01**）。
 */
final class ShuyunOpenPlatformOrderTradeSourceResolver
{
    public const UNKNOWN_LOG_KEYWORD = 'shuyun_open_platform_trade_source_unknown';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return non-empty-string|null  数云 {@see trade_source} 字面量；未映射返回 null
     */
    public function resolveTradeSourceForOrder(int $companyId, string $orderId, string $orderClass): ?string
    {
        $normalized = strtolower(trim($orderClass));
        if ($normalized === '') {
            $this->logUnknown($companyId, $orderId, $orderClass, $normalized, 'empty_order_class');

            return null;
        }

        /** @var array<string, scalar> $map */
        $map = config('shuyun_open_platform.order_class_trade_source_map', []);
        if (!isset($map[$normalized])) {
            $this->logUnknown($companyId, $orderId, $orderClass, $normalized, 'missing_map_key');

            return null;
        }

        $raw = $map[$normalized];
        $value = is_scalar($raw) ? trim((string) $raw) : '';
        if ($value === '') {
            $this->logUnknown($companyId, $orderId, $orderClass, $normalized, 'empty_map_value');

            return null;
        }

        return $value;
    }

    private function logUnknown(int $companyId, string $orderId, string $orderClass, string $normalized, string $reason): void
    {
        $this->logger->error(self::UNKNOWN_LOG_KEYWORD.': trade_source not configured for order_class', [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'order_class' => $orderClass,
            'order_class_normalized' => $normalized,
            'reason' => $reason,
        ]);
    }
}
