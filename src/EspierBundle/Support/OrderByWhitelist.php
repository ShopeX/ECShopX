<?php

declare(strict_types=1);

namespace EspierBundle\Support;

/**
 * Whitelist helper for ORDER BY column names and sort directions.
 */
class OrderByWhitelist
{
    /**
     * Build a sanitized order-by map from a "column direction" sort string.
     *
     * @param array<string, string> $defaultOrder
     * @param array<string, string> $appendOrder
     *
     * @return array<string, string>
     */
    public static function fromSortString(
        string $sort,
        array $allowedColumns,
        array $defaultOrder = [],
        array $appendOrder = []
    ): array {
        $sort = trim($sort);
        if ($sort === '') {
            return self::mergeOrder(self::sanitize($defaultOrder, $allowedColumns), $appendOrder, $allowedColumns);
        }

        $parts = preg_split('/\s+/', $sort, 2);
        $column = $parts[0] ?? '';
        $direction = $parts[1] ?? 'ASC';

        $normalizedDirection = self::normalizeDirection($direction);
        if (!self::isAllowedColumn($column, $allowedColumns) || $normalizedDirection === null) {
            return self::mergeOrder(self::sanitize($defaultOrder, $allowedColumns), $appendOrder, $allowedColumns);
        }

        $orderBy = [$column => $normalizedDirection];

        return self::mergeOrder($orderBy, $appendOrder, $allowedColumns);
    }

    /**
     * @param array<string, string> $orderBy
     *
     * @return array<string, string>
     */
    public static function sanitize(array $orderBy, array $allowedColumns): array
    {
        $sanitized = [];
        foreach ($orderBy as $column => $direction) {
            if (!self::isAllowedColumn((string) $column, $allowedColumns)) {
                continue;
            }
            $normalizedDirection = self::normalizeDirection((string) $direction);
            if ($normalizedDirection === null) {
                continue;
            }
            $sanitized[$column] = $normalizedDirection;
        }

        return $sanitized;
    }

    /**
     * @param array<string, string> $orderBy
     */
    public static function applyToQueryBuilder(
        \Doctrine\DBAL\Query\QueryBuilder $qb,
        array $orderBy,
        array $allowedColumns
    ): void {
        foreach (self::sanitize($orderBy, $allowedColumns) as $field => $direction) {
            $qb->addOrderBy($field, $direction);
        }
    }

    public static function isAllowedColumn(string $column, array $allowedColumns): bool
    {
        return in_array($column, $allowedColumns, true);
    }

    public static function normalizeDirection(?string $direction): ?string
    {
        if ($direction === null) {
            return null;
        }

        $normalized = strtoupper(trim($direction));
        if ($normalized === 'ASC' || $normalized === 'DESC') {
            return $normalized;
        }

        return null;
    }

    /**
     * @param array<string, string> $base
     * @param array<string, string> $append
     *
     * @return array<string, string>
     */
    private static function mergeOrder(array $base, array $append, array $allowedColumns): array
    {
        return self::sanitize(array_merge($base, $append), $allowedColumns);
    }
}
