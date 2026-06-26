<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 商城退款状态 / 售后类型 → 数云 refund.sync 字典（公共字典 §6.6 / §6.7）。
 */
final class ShuyunOpenPlatformNormalOrderRefundStatusMapper
{
    /**
     * @param  array<string, mixed>  $refund  {@see AftersalesRefundRepository::getAftersalesRefundData}
     */
    public static function mapRefundStatus(array $refund): string
    {
        $st = (string) ($refund['refund_status'] ?? '');

        return match ($st) {
            'SUCCESS' => 'SY_REFUND_SUCC',
            'REFUSE', 'REFUNDCLOSE' => 'SY_REFUND_FAIL',
            'CANCEL' => 'SY_REFUND_FAIL',
            'CHANGE' => 'SY_REFUND_FAIL',
            'READY', 'AUDIT_SUCCESS' => 'SY_CHECKING',
            'PROCESSING' => 'SY_REFUNDING',
            default => 'SY_REFUNDING',
        };
    }

    public static function mapGoodReturnFromAftersalesDetailType(string $aftersalesType): string
    {
        return match ($aftersalesType) {
            'REFUND_GOODS', 'EXCHANGING_GOODS' => 'SY_RETURN_FEE_GOOD',
            default => 'SY_ONLY_FEE',
        };
    }

    /**
     * @param  array<string, mixed>  $order  普通订单行
     */
    public static function resolveRefundPhase(array $order, mixed $refundType): int
    {
        if ($refundType === 1 || $refundType === '1') {
            return 1;
        }

        $os = (string) ($order['order_status'] ?? '');

        return $os === 'DONE' ? 2 : 1;
    }
}
