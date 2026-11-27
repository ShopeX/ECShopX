<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OrdersBundle\Listeners;

use AftersalesBundle\Services\AftersalesRefundService;
use DataCubeBundle\Services\TrackService;
use MembersBundle\Services\MemberService;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Events\NormalOrderPaySuccessEvent;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Repositories\TradeRepository;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Traits\GetOrderServiceTrait;

class UpdateOrderStatusListener
{
    use GetOrderServiceTrait;

    /** @var list<string> */
    private const POST_PAY_ORDER_STATUSES = [
        'PAYED',
        'WAIT_GROUPS_SUCCESS',
        'WAIT_BUYER_CONFIRM',
        'DONE',
    ];

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        $orderId = $event->entities->getOrderId();

        return $this->handle_common_order($event);
    }

    private function handle_common_order($event)
    {
        app('log')->debug('订单号更新：' . json_encode($event, 256));
        $userId = $event->entities->getUserId();
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        $payType = $event->entities->getPayType();
        $tradeId = $event->entities->getTradeId();
        if (!$orderId) {
            return false;
        }

        $paymentstate = $event->entities->getTradeState();
        if ($paymentstate != 'SUCCESS') {
            return true;
        }

        $trade = $this->loadTradeForReconcile((int) $companyId, (string) $tradeId);
        if ($trade === []) {
            app('log')->warning('trade_finish_reconcile_missing_trade', [
                'order_id' => $orderId,
                'company_id' => $companyId,
                'trade_id' => $tradeId,
            ]);

            return false;
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($companyId, $orderId);
            if (!$order) {
                app('log')->debug('订单号'.$orderId.' 不存在');

                return false;
            }

            $branch = $this->resolveReconcileBranch($order, (string) $tradeId);
            if ($branch === 'payable') {
                $advanced = $this->handlePayableBranch($order, $payType, $companyId, $orderId, $userId, (string) $tradeId);
                if ($advanced === 'cas_retry') {
                    continue;
                }

                return $advanced === 'success';
            }

            return $this->handleNonPayableBranch($branch, $order, $trade, $companyId, $orderId, $tradeId);
        }

        app('log')->info('trade_finish_reconcile_cas_exhausted', [
            'order_id' => $orderId,
            'company_id' => $companyId,
            'trade_id' => $tradeId,
        ]);

        return false;
    }

    /**
     * @param  array<string, mixed>  $order
     */
    protected function resolveReconcileBranch(array $order, string $currentTradeId): string
    {
        $status = (string) ($order['order_status'] ?? '');

        if ($status === 'PART_PAYMENT') {
            return 'payable';
        }

        if ($status === 'NOTPAY') {
            return $this->isNotPayTimedOut($order) ? 'pay_after_cancel' : 'payable';
        }

        if ($status === 'CANCEL') {
            return 'pay_after_cancel';
        }

        if (in_array($status, self::POST_PAY_ORDER_STATUSES, true)) {
            return $this->hasOtherSuccessfulNonPointTrade(
                (int) ($order['company_id'] ?? 0),
                (string) ($order['order_id'] ?? ''),
                $currentTradeId
            ) ? 'dup_pay' : 'same_trade_ignore';
        }

        return 'unhandled';
    }

    /**
     * @param  array<string, mixed>  $order
     */
    protected function isNotPayTimedOut(array $order, ?int $now = null): bool
    {
        if (($order['order_status'] ?? '') !== 'NOTPAY') {
            return false;
        }

        $autoCancel = (int) ($order['auto_cancel_time'] ?? 0);

        return $autoCancel > 0 && $autoCancel <= ($now ?? time());
    }

    protected function hasOtherSuccessfulNonPointTrade(int $companyId, string $orderId, string $excludeTradeId): bool
    {
        if ($companyId < 1 || $orderId === '') {
            return false;
        }

        /** @var TradeRepository $tradeRepository */
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $tradeList = $tradeRepository->lists([
            'company_id' => $companyId,
            'order_id' => $orderId,
            'trade_state' => 'SUCCESS',
        ], [], 100, 1);

        foreach ($tradeList['list'] ?? [] as $row) {
            $rowTradeId = (string) ($row['trade_id'] ?? $row['tradeId'] ?? '');
            if ($rowTradeId !== '' && $rowTradeId === $excludeTradeId) {
                continue;
            }
            $rowPayType = strtolower((string) ($row['pay_type'] ?? $row['payType'] ?? ''));
            if ($rowPayType !== 'point') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadTradeForReconcile(int $companyId, string $tradeId): array
    {
        if ($companyId < 1 || $tradeId === '') {
            return [];
        }

        $tradeService = new TradeService();

        return $tradeService->getInfo(['company_id' => $companyId, 'trade_id' => $tradeId]) ?: [];
    }

    /**
     * @param  array<string, mixed>  $order
     * @return 'success'|'cas_retry'|'failed'
     */
    private function handlePayableBranch(array $order, string $payType, $companyId, string $orderId, $userId, string $tradeId = ''): string
    {
        $orderService = $this->getOrderServiceByOrderInfo($order);

        try {
            $updated = $orderService->tradeSuccUpdateOrderStatus($order, $payType);
            if ($updated === false) {
                app('log')->info('trade_finish_reconcile_cas_miss', [
                    'order_id' => $orderId,
                    'company_id' => $companyId,
                    'trade_id' => $tradeId,
                    'order_status' => $order['order_status'] ?? null,
                    'result' => 'cas_retry',
                ]);

                return 'cas_retry';
            }

            $membersService = new MemberService();
            $memFilter = [
                'user_id' => $order['user_id'],
                'company_id' => $order['company_id'],
            ];
            try {
                $membersService->memberInfoUpdate(['have_consume' => true], $memFilter);
            } catch (\Exception $memberEx) {
                app('log')->warning('trade_finish_member_info_update_skipped', [
                    'order_id' => $orderId,
                    'company_id' => $order['company_id'] ?? null,
                    'user_id' => $order['user_id'] ?? null,
                    'event_user_id' => $userId,
                    'message' => $memberEx->getMessage(),
                ]);
            }

            $trackService = new TrackService();
            $trackService->addEntriesNum([
                'monitor_id' => $order['monitor_id'],
                'company_id' => $companyId,
                'source_id' => $order['source_id'],
            ]);

            event(new OrderProcessLogEvent([
                'order_id' => $orderId,
                'company_id' => $companyId,
                'operator_type' => 'system',
                'is_show' => true,
                'remarks' => '订单支付',
                'detail' => '订单号：' . $orderId . '，订单支付成功',
            ]));

            event(new NormalOrderPaySuccessEvent([
                'company_id' => $companyId,
                'order_id' => $order['order_id'],
                'user_id' => (int) ($order['user_id'] ?? 0),
                'total_fee' => $order['total_fee'] ?? 0,
            ]));

            app('log')->info('trade_finish_reconcile_pay_success', [
                'order_id' => $orderId,
                'company_id' => $companyId,
                'trade_id' => $tradeId,
                'order_status' => $order['order_status'] ?? null,
                'result' => 'pay_success',
            ]);

            return 'success';
        } catch (\Exception $e) {
            app('log')->debug('订单号:'.$orderId.', 状态更新错误: '.$e->getMessage());

            return 'failed';
        }
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>  $trade
     */
    private function handleNonPayableBranch(
        string $branch,
        array $order,
        array $trade,
        $companyId,
        string $orderId,
        string $tradeId
    ): bool {
        $logContext = [
            'order_id' => $orderId,
            'company_id' => $companyId,
            'trade_id' => $tradeId,
            'order_status' => $order['order_status'] ?? null,
        ];

        if ($branch === 'pay_after_cancel') {
            /** @var AftersalesRefundService $refundService */
            $refundService = app(AftersalesRefundService::class);
            $refund = $refundService->createAbnormalTradeRefund($order, $trade, 'PAY_AFTER_CANCEL');
            app('log')->info('trade_finish_reconcile_pay_after_cancel', array_merge($logContext, [
                'refund_status' => $refund['refund_status'] ?? 'PAY_AFTER_CANCEL',
                'refund_bn' => $refund['refund_bn'] ?? '',
                'result' => 'created',
            ]));

            return false;
        }

        if ($branch === 'dup_pay') {
            /** @var AftersalesRefundService $refundService */
            $refundService = app(AftersalesRefundService::class);
            $refund = $refundService->createAbnormalTradeRefund($order, $trade, 'DUP_PAY');
            app('log')->info('trade_finish_reconcile_dup_pay', array_merge($logContext, [
                'refund_status' => $refund['refund_status'] ?? 'DUP_PAY',
                'refund_bn' => $refund['refund_bn'] ?? '',
                'result' => 'created',
            ]));

            return false;
        }

        if ($branch === 'same_trade_ignore') {
            app('log')->info('trade_finish_reconcile_same_trade_ignore', array_merge($logContext, [
                'result' => 'ignored',
            ]));

            return false;
        }

        app('log')->info('trade_finish_reconcile_unhandled', array_merge($logContext, [
            'result' => 'skipped',
        ]));

        return false;
    }
}