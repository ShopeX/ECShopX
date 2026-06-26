<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use Illuminate\Support\Facades\Log;
use OrdersBundle\Events\NormalOrderCancelEvent;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitConsumePushService;

/**
 * {@see NormalOrderCancelEvent} → 数云线下权益核销 NOT_USED（券已返还后语义；§4.3）。
 */
final class ShuyunOfflineBenefitResultPushOnOrderCancelListener
{
    public function handle(NormalOrderCancelEvent $event): void
    {
        $payload = $event->entities ?? null;
        if (!\is_array($payload)) {
            return;
        }

        $companyId = (int) ($payload['company_id'] ?? 0);
        if (array_key_exists('user_id', $payload) && (int) $payload['user_id'] === 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun offline_benefit_consume cancel dispatch skipped: user_id is zero.', [
                'company_id' => $companyId,
            ]);

            return;
        }
        if (array_key_exists('total_fee', $payload) && (int) $payload['total_fee'] <= 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun offline_benefit_consume cancel dispatch skipped: total_fee is zero (fen).', [
                'company_id' => $companyId,
            ]);

            return;
        }

        $orderId = self::parseOrderId($payload['order_id'] ?? null);
        if ($companyId < 1 || $orderId < 1) {
            return;
        }

        app(ShuyunOfflineBenefitConsumePushService::class)->handleOrderCancel($companyId, $orderId);
    }

    private static function parseOrderId(mixed $raw): int
    {
        if (\is_int($raw)) {
            return $raw > 0 ? $raw : 0;
        }
        if (\is_string($raw) && ctype_digit($raw)) {
            return (int) $raw;
        }

        return 0;
    }
}
