<?php

declare(strict_types=1);

namespace AftersalesBundle\Support;

use Dingo\Api\Exception\ResourceException;

/**
 * Aftersales store-scope gate: enforce own-distributor access (no HQ proxy in this plan).
 */
class AftersalesDistributorGate
{
    /**
     * @param array<string, mixed> $params
     */
    public static function attachApiAuthScope(array &$params, $user): void
    {
        $distributorListSet = $user->get('distributor_ids');
        if (!empty($distributorListSet)) {
            $params['allowed_distributor_ids'] = array_column($distributorListSet, 'distributor_id');
        }
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $authInfo
     */
    public static function attachAdminAuthScope(array &$params, array $authInfo): void
    {
        if (!empty($authInfo['distributor_id'])) {
            $params['distributor_id'] = (int) $authInfo['distributor_id'];
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return int|int[]|null
     */
    public static function resolveScopeFromParams(array $params)
    {
        if (!empty($params['allowed_distributor_ids'])) {
            return $params['allowed_distributor_ids'];
        }
        if (!empty($params['distributor_id'])) {
            return (int) $params['distributor_id'];
        }

        return null;
    }

    /**
     * Reject unauthorized distributor_id filter instead of silently dropping store scope.
     *
     * @param array<string, mixed> $filter
     */
    public static function assertAuthorizedDistributorFilter(array &$filter, $user): void
    {
        $distributorListSet = $user->get('distributor_ids');
        if (empty($distributorListSet)) {
            return;
        }

        $distributorIdSet = array_map('intval', array_column($distributorListSet, 'distributor_id'));

        if (!empty($filter['distributor_id'])) {
            if (is_array($filter['distributor_id'])) {
                $intersected = array_values(array_intersect(
                    array_map('intval', $filter['distributor_id']),
                    $distributorIdSet
                ));
                if ($intersected === []) {
                    throw new ResourceException('无权查看该店铺售后');
                }
                $filter['distributor_id'] = $intersected;
            } elseif (!in_array((int) $filter['distributor_id'], $distributorIdSet, true)) {
                throw new ResourceException('无权查看该店铺售后');
            } else {
                $filter['distributor_id'] = (int) $filter['distributor_id'];
            }
        } else {
            $filter['distributor_id'] = $distributorIdSet;
        }
    }

    /**
     * @param array<string, mixed> $aftersales
     * @param int|int[]|null $scopeDistributorId single store (admin wxapp) or allowed IDs (api store staff)
     */
    public static function assertOwnStoreAftersales(array $aftersales, $scopeDistributorId): void
    {
        if ($scopeDistributorId === null || $scopeDistributorId === 0 || $scopeDistributorId === []) {
            return;
        }

        $aftersalesDistributorId = (int) ($aftersales['distributor_id'] ?? 0);

        if (is_array($scopeDistributorId)) {
            $allowed = array_map('intval', $scopeDistributorId);
            if (!in_array($aftersalesDistributorId, $allowed, true)) {
                throw new ResourceException('无权操作该售后单');
            }

            return;
        }

        if ($aftersalesDistributorId !== (int) $scopeDistributorId) {
            throw new ResourceException('无权操作该售后单');
        }
    }
}
