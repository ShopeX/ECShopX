<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 普通订单 DB 行 → 数云 trade.sync 枚举/时间格式（与计划 Q2/Q3/Q4/Q6 渐进对齐，执行期可在 decisions.md 细化）。
 */
final class ShuyunOpenPlatformNormalOrderShuyunTradeMapper
{
    /**
     * @param  array<string, mixed>  $order  {@see NormalOrdersRepository::getServiceOrderData}
     */
    public static function mapOrderStatus(array $order): string
    {
        $os = (string) ($order['order_status'] ?? '');
        $ds = (string) ($order['delivery_status'] ?? '');

        return match ($os) {
            'DONE' => 'TRADE_FINISHED',
            'CANCEL' => 'TRADE_CLOSED',
            // 商城「待收货」→ 公共字典 §6.1：WAIT_BUYER_CONFIRM_GOODS
            'WAIT_BUYER_CONFIRM' => 'WAIT_BUYER_CONFIRM_GOODS',
            // 货到付款：已发货待买家付款（§6.1 WAIT_BUYER_CONFIRM_PAY）
            'WAIT_PAID_CONFIRM' => 'WAIT_BUYER_CONFIRM_PAY',
            // 拼团成功待支付（与 NOTPAY 同属待付）
            'WAIT_GROUPS_SUCCESS' => 'WAIT_BUYER_PAY',
            'NOTPAY' => 'WAIT_BUYER_PAY',
            'PART_PAYMENT' => 'WAIT_BUYER_PAY',
            // OME/审核通过后待出库/发货，与 PAYED 共用发货维度（见 AbstractNormalOrder::getOrderStatusMsg）
            'REVIEW_PASS' => self::mapShippedProgressByDeliveryStatus($ds),
            'PAYED' => self::mapShippedProgressByDeliveryStatus($ds),
            // 订单主状态级「退款成功」→ §6.1 全额退关闭（与部分退款行级 refund.sync 并存）
            'REFUND_SUCCESS' => 'TRADE_CLOSED_ALL_REFUND',
            // 数云 §6.1 无单独「退款处理中」主状态；保守按「已付待发」出站，避免误标 TRADE_CLOSED（付款前关闭）
            'REFUND_PROCESS' => 'WAIT_SELLER_SEND_GOODS',
            // 其余未识别：保守映射为待付款，避免捏造已发货（与 D-TRADE-SOURCE 精神一致，异常组合可再打观测日志）
            default => 'WAIT_BUYER_PAY',
        };
    }

    /**
     * 已付/已审可履约订单，按发货进度映射 §6.1。
     */
    private static function mapShippedProgressByDeliveryStatus(string $deliveryStatus): string
    {
        return match ($deliveryStatus) {
            'DONE' => 'WAIT_BUYER_CONFIRM_GOODS',
            'PARTAIL' => 'SELLER_CONSIGNED_PART',
            default => 'WAIT_SELLER_SEND_GOODS',
        };
    }

    /**
     * @param  array<string, mixed>  $order
     */
    public static function mapDeliveryType(array $order): string
    {
        $receipt = (string) ($order['receipt_type'] ?? '');

        return match ($receipt) {
            'ziti' => 'SY_SELFLIFT',
            'dada' => 'SY_INTRA_CITY_SERVICE',
            'merchant' => 'SY_NONE',
            default => 'SY_EXPRESS',
        };
    }

    /**
     * 结单类状态须带 endtime（计划 **Q3**）。
     *
     * @param  array<string, mixed>  $order
     */
    public static function shouldSendEndTime(string $shuyunOrderStatus): bool
    {
        return in_array($shuyunOrderStatus, ['TRADE_FINISHED', 'TRADE_CLOSED', 'TRADE_CLOSED_ALL_REFUND'], true);
    }

    public static function formatDateTime(int $unixTs): string
    {
        if ($unixTs <= 0) {
            return date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $unixTs);
    }
}
