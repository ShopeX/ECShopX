<?php

declare(strict_types=1);

namespace PaymentBundle\Support;

final class ChinaumsNotifyValidator
{
    public static function extractTradeIdFromMerOrderId(string $merOrderId): string
    {
        $prefix = (string) config('ums.pre');
        if (!str_starts_with($merOrderId, $prefix)) {
            throw new \InvalidArgumentException('merOrderId prefix mismatch');
        }

        return substr($merOrderId, strlen($prefix));
    }

    public static function assertNotifyAmountMatchesTrade(array $payload, array $tradeInfo): void
    {
        $amount = $payload['totalAmount'] ?? $payload['totalAmt'] ?? null;
        if ((int) $amount !== (int) $tradeInfo['pay_fee']) {
            throw new \InvalidArgumentException('notify amount does not match trade pay_fee');
        }
    }

    public static function assertTradeExists(array $tradeInfo): void
    {
        if ($tradeInfo === []) {
            throw new \InvalidArgumentException('trade not found');
        }
    }

    public static function shouldUpdateTradeToSuccess(string $status, array $tradeInfo): bool
    {
        if ($status !== 'TRADE_SUCCESS') {
            return false;
        }

        return ($tradeInfo['trade_state'] ?? '') !== 'SUCCESS';
    }
}
