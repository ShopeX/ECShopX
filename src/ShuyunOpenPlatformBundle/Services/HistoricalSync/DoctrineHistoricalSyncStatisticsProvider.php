<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

use Doctrine\DBAL\Connection;

final class DoctrineHistoricalSyncStatisticsProvider implements HistoricalSyncStatisticsProviderInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function collect(int $companyId): array
    {
        $shopsTotal = (int) $this->scalar(
            'SELECT COUNT(*) FROM distribution_distributor WHERE company_id = ?',
            [$companyId]
        );

        $members = $this->connection->fetchAssociative(
            'SELECT COUNT(*) AS total,
                SUM(CASE WHEN mobile IS NULL OR TRIM(mobile) = \'\' THEN 1 ELSE 0 END) AS no_mobile,
                SUM(CASE WHEN mobile IS NOT NULL AND TRIM(mobile) <> \'\' AND mobile NOT REGEXP ? THEN 1 ELSE 0 END) AS invalid_mobile,
                SUM(CASE WHEN shuyun_open_online_wxapp_sync_at IS NOT NULL THEN 1 ELSE 0 END) AS already_wxapp_synced
             FROM members WHERE company_id = ?',
            ['^1[0-9]{10}$', $companyId]
        ) ?: [];

        $membersTotal = (int) ($members['total'] ?? 0);
        $noMobile = (int) ($members['no_mobile'] ?? 0);
        $invalidMobile = (int) ($members['invalid_mobile'] ?? 0);
        $alreadyWxapp = (int) ($members['already_wxapp_synced'] ?? 0);
        $membersEligible = max(0, $membersTotal - $noMobile - $invalidMobile);

        $ordersTotal = (int) $this->scalar(
            'SELECT COUNT(*) FROM orders_normal_orders WHERE company_id = ?',
            [$companyId]
        );
        $ordersEligible = (int) $this->scalar(
            'SELECT COUNT(*) FROM orders_normal_orders
             WHERE company_id = ?
               AND user_id > 0 AND total_fee > 0
               AND pay_status = ?
               AND order_class IN (?, ?)',
            [$companyId, 'PAYED', 'normal', 'shopadmin']
        );

        $refundsEligible = (int) $this->scalar(
            'SELECT COUNT(*) FROM aftersales_refund WHERE company_id = ? AND refund_status = ?',
            [$companyId, 'SUCCESS']
        );

        $categories = (int) $this->scalar(
            'SELECT COUNT(*) FROM items_category WHERE company_id = ? AND category_level IN (2, 3)',
            [$companyId]
        );

        $productUnits = (int) $this->scalar(
            'SELECT COUNT(DISTINCT CONCAT(di.distributor_id, \':\', i.default_item_id))
             FROM distribution_distributor_items di
             INNER JOIN items i ON di.item_id = i.item_id AND i.company_id = di.company_id
             WHERE di.company_id = ? AND i.default_item_id IS NOT NULL AND i.default_item_id > 0',
            [$companyId]
        );

        $pointsEligible = (int) $this->scalar(
            'SELECT COUNT(*) FROM point_member WHERE company_id = ? AND point > 0',
            [$companyId]
        );

        return [
            'shops' => ['total' => $shopsTotal, 'eligible' => $shopsTotal],
            'categories' => ['total' => $categories, 'eligible' => $categories],
            'products' => ['total' => $productUnits, 'eligible' => $productUnits, 'product_units' => $productUnits],
            'members' => [
                'total' => $membersTotal,
                'eligible' => $membersEligible,
                'invalid' => $noMobile + $invalidMobile,
                'no_mobile' => $noMobile,
                'invalid_mobile' => $invalidMobile,
                'already_wxapp_synced' => $alreadyWxapp,
            ],
            'orders' => [
                'total' => $ordersTotal,
                'eligible' => $ordersEligible,
                'skipped' => max(0, $ordersTotal - $ordersEligible),
            ],
            'refunds' => ['total' => $refundsEligible, 'eligible' => $refundsEligible],
            'points' => ['total' => $pointsEligible, 'eligible' => $pointsEligible],
        ];
    }

    /**
     * @param  list<mixed>  $params
     */
    private function scalar(string $sql, array $params): string|int|float
    {
        $v = $this->connection->fetchOne($sql, $params);

        return $v ?? 0;
    }
}
