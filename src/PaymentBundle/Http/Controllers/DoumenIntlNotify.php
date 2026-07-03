<?php

declare(strict_types=1);

namespace PaymentBundle\Http\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Services\Payments\DoumenIntlService;

class DoumenIntlNotify extends BaseController
{
    private const SUCCESS_CODE = '00000000';

    public function handle(Request $request): JsonResponse
    {
        $rawBody = (string) $request->getContent();
        $signature = (string) $request->header('X-Signature', '');

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            app('log')->warning('doumen_intl_notify_invalid_payload');

            return $this->jsonAck('10000001', 'INVALID_PAYLOAD');
        }

        $data = $payload;
        app('log')->info('doumen_intl_notify_payload', ['payload' => $payload]);
        app('log')->info('doumen_intl_notify_payload_signature', ['signature' => $signature]);
        if (! is_array($data)) {
            $data = [];
        }

        $tradeId = $data['merchantOrderId'] ?? null;
        $gatewayStatus = (string) ($data['status'] ?? '');
        $transactionId = $data['id'] ?? null;

        if (! is_string($tradeId) || $tradeId === '') {
            app('log')->warning('doumen_intl_notify_missing_merchant_order_id');

            return $this->jsonAck('10000002', 'MISSING_MERCHANT_ORDER_ID');
        }

        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['trade_id' => $tradeId]);
        app('log')->info('doumen_intl_notify_trade_info', ['trade_info' => $tradeInfo]);

        if (empty($tradeInfo)) {
            app('log')->warning('doumen_intl_notify_unknown_trade', [
                'trade_id' => $tradeId,
                'gateway_status' => $gatewayStatus,
            ]);

            return $this->jsonAck('10000003', 'UNKNOWN_TRADE');
        }

        $doumenIntlService = new DoumenIntlService();
        if (! $doumenIntlService->verifyNotifySignature((int) $tradeInfo['company_id'], $rawBody, $signature)) {
            app('log')->warning('doumen_intl_notify_invalid_signature', [
                'trade_id' => $tradeId,
                'signature' => $signature,
            ]);

            return $this->jsonAck('10000004', 'SIGNATURE_VERIFICATION_FAILED');
        }

        if (in_array($gatewayStatus, ['PENDING', 'RETRY_PENDING'], true)) {
            app('log')->info('doumen_intl_notify_pending_status', [
                'trade_id' => $tradeId,
                'gateway_status' => $gatewayStatus,
            ]);

            return $this->jsonAck(self::SUCCESS_CODE, 'SUCCESS');
        }

        if ($gatewayStatus === 'SUCCEED') {
            if (($tradeInfo['trade_state'] ?? '') === 'SUCCESS') {
                return $this->jsonAck(self::SUCCESS_CODE, 'SUCCESS');
            }

            $tradeService->updateStatus($tradeId, 'SUCCESS', [
                'pay_type' => 'doumen_intl',
                'transaction_id' => $transactionId,
            ]);

            return $this->jsonAck(self::SUCCESS_CODE, 'SUCCESS');
        }

        if ($gatewayStatus === 'FAILED') {
            if (in_array($tradeInfo['trade_state'] ?? '', ['PAYERROR', 'SUCCESS'], true)) {
                return $this->jsonAck(self::SUCCESS_CODE, 'SUCCESS');
            }

            $tradeService->updateStatus($tradeId, 'PAYERROR', [
                'pay_type' => 'doumen_intl',
                'transaction_id' => $transactionId,
            ]);

            return $this->jsonAck(self::SUCCESS_CODE, 'SUCCESS');
        }

        app('log')->info('doumen_intl_notify_unhandled_status', [
            'trade_id' => $tradeId,
            'gateway_status' => $gatewayStatus,
        ]);

        return $this->jsonAck(self::SUCCESS_CODE, 'SUCCESS');
    }

    private function jsonAck(string $code, string $message): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
        ], 200);
    }
}
