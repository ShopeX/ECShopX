<?php

declare(strict_types=1);

namespace OrdersBundle\Services;

use OrdersBundle\Entities\Trade;
use OrdersBundle\Repositories\TradeRepository;

/**
 * 支付完成后的外部集成（数云 trade.sync、SaasErp 等）在读取订单主表时，
 * 须确认主单已脱离「待付/部分付」可见状态，避免支付外层事务未提交时误推。
 */
class OrderPostPayIntegrationReadinessService
{
    private TradeRepository $tradeRepository;

    public function __construct(?TradeRepository $tradeRepository = null)
    {
        $this->tradeRepository = $tradeRepository ?? app('registry')->getManager('default')->getRepository(Trade::class);
    }

    /**
     * @param  array<string, mixed>  $orderRow
     */
    public function isOrderRowReadyForPostPayIntegration(array $orderRow): bool
    {
        if ($orderRow === []) {
            return false;
        }

        if ((string) ($orderRow['pay_status'] ?? '') === 'PAYED') {
            return true;
        }

        $orderStatus = (string) ($orderRow['order_status'] ?? '');

        return ! in_array($orderStatus, ['NOTPAY', 'PART_PAYMENT'], true);
    }

    public function isOrderReadyForPostPayIntegration(int $companyId, string $orderId): bool
    {
        if ($companyId < 1 || $orderId === '') {
            return false;
        }

        /** @var \OrdersBundle\Repositories\NormalOrdersRepository $normalOrdersRepository */
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(\OrdersBundle\Entities\NormalOrders::class);
        $orderRow = $normalOrdersRepository->getInfo([
            'company_id' => $companyId,
            'order_id' => $orderId,
        ]);

        return $this->isOrderRowReadyForPostPayIntegration($orderRow);
    }

    public function orderHasSuccessfulTrade(int $companyId, string $orderId): bool
    {
        if ($companyId < 1 || $orderId === '') {
            return false;
        }

        $trade = $this->tradeRepository->getInfo([
            'company_id' => (string) $companyId,
            'order_id' => $orderId,
            'trade_state' => 'SUCCESS',
        ]);

        return $trade !== [] && $trade !== null;
    }
}
