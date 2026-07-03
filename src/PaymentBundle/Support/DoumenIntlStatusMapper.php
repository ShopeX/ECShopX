<?php

declare(strict_types=1);

namespace PaymentBundle\Support;

final class DoumenIntlStatusMapper
{
    /**
     * @return array{status: string, msg?: string}
     */
    public static function mapPaymentQueryStatus(string $gatewayStatus): array
    {
        return match ($gatewayStatus) {
            'SUCCEED' => ['status' => 'SUCCESS'],
            'FAILED' => ['status' => 'FAIL'],
            'PENDING', 'RETRY_PENDING' => ['status' => 'NOTPAY', 'msg' => '处理中'],
            default => ['status' => 'NOTPAY', 'msg' => '处理中'],
        };
    }
}
