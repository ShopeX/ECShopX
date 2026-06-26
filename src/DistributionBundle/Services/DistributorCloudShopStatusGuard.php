<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace DistributionBundle\Services;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\Orders\NormalOrderService;
use SelfserviceBundle\Entities\RegistrationActivity;
use SelfserviceBundle\Entities\RegistrationRecord;
use SelfserviceBundle\Services\RegistrationActivityRelShopService;

/**
 * 云店营业状态变更守卫：禁用云店 / 闭店 / 撤店前的订单与活动校验。
 *
 * 店铺状态仅使用 {@see Distributor} 的 is_valid 列，取值如下（与 DB 存值一致）：
 * - IS_VALID_CLOUD_ENABLED  ('true'):  启用云店
 * - IS_VALID_CLOUD_DISABLED ('false'): 禁用云店
 * - IS_VALID_CLOSED         ('closed'): 闭店（店铺仍存在，非撤店）
 * - IS_VALID_REVOKED        ('delete'): 撤店（废弃）
 */
final class DistributorCloudShopStatusGuard
{
    /** @var string 启用云店 */
    public const IS_VALID_CLOUD_ENABLED = 'true';

    /** @var string 禁用云店 */
    public const IS_VALID_CLOUD_DISABLED = 'false';

    /** @var string 闭店 */
    public const IS_VALID_CLOSED = 'closed';

    /** @var string 撤店 */
    public const IS_VALID_REVOKED = 'delete';

    /**
     * @return list<string>
     */
    public static function allowedIsValidValues(): array
    {
        return [
            self::IS_VALID_CLOUD_ENABLED,
            self::IS_VALID_CLOUD_DISABLED,
            self::IS_VALID_CLOSED,
            self::IS_VALID_REVOKED,
        ];
    }

    /**
     * 店铺列表 `is_valid=cloud_all`（启用云店 + 禁用云店 + 闭店，不含撤店）用于 SQL IN 的取值集合。
     *
     * 除文档约定的 true/false/closed 外，库内仍存在与 {@see \ShuyunOpenPlatformBundle\Services\ShopSyncLifecycleResolver}
     * 一致的历史存值 '1'/'0'，若 IN 不包含则列表会误查为 0 条。
     *
     * @return list<string>
     */
    public static function isValidValuesForCloudAllListFilter(): array
    {
        return [
            self::IS_VALID_CLOUD_ENABLED,
            self::IS_VALID_CLOUD_DISABLED,
            self::IS_VALID_CLOSED,
            '1',
            '0',
        ];
    }

    /**
     * 将请求体中的 is_valid 规范为 allowedIsValidValues 之一（兼容布尔/数字）。
     */
    public static function normalizeIncomingIsValid($raw): string
    {
        if ($raw === true || $raw === 1) {
            return self::IS_VALID_CLOUD_ENABLED;
        }
        if ($raw === false || $raw === 0) {
            return self::IS_VALID_CLOUD_DISABLED;
        }
        $s = strtolower(trim((string) $raw));
        if ($s === '1' || $s === 'true') {
            return self::IS_VALID_CLOUD_ENABLED;
        }
        if ($s === '0' || $s === 'false') {
            return self::IS_VALID_CLOUD_DISABLED;
        }
        if ($s === self::IS_VALID_CLOSED) {
            return self::IS_VALID_CLOSED;
        }
        if ($s === self::IS_VALID_REVOKED) {
            return self::IS_VALID_REVOKED;
        }

        return $s;
    }

    /**
     * 归一化 is_valid 字符串（与 DB 一致，不做 true/false 布尔混用）。
     */
    public static function normalizeIsValidFromRow(array $row): string
    {
        $v = (string) ($row['is_valid'] ?? self::IS_VALID_CLOUD_ENABLED);

        return $v;
    }

    /**
     * 是否需要做「未完成订单」校验（离开闭店 → 启用云店不做订单校验）。
     */
    public static function requiresOpenNormalOrdersCheck(string $beforeIsValid, string $afterIsValid): bool
    {
        if ($afterIsValid === self::IS_VALID_REVOKED) {
            return true;
        }
        if ($afterIsValid === self::IS_VALID_CLOSED && $beforeIsValid !== self::IS_VALID_CLOSED) {
            return true;
        }
        if ($afterIsValid === self::IS_VALID_CLOUD_DISABLED && $beforeIsValid !== self::IS_VALID_CLOUD_DISABLED) {
            return true;
        }

        return false;
    }

    /**
     * 未完成普通订单：非已完成、非已取消（不按售后状态额外过滤）。
     */
    public static function countOpenNormalOrders(int $companyId, int $distributorId): int
    {
        $orderService = new OrderService(new NormalOrderService());
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'order_type' => 'normal',
            'order_status|notin' => ['DONE', 'CANCEL'],
        ];

        return $orderService->countOrderNum($filter);
    }

    public static function assertTransitionAllowed(
        int $companyId,
        int $distributorId,
        array $beforeRow,
        string $afterIsValid
    ): void {
        $before = self::normalizeIsValidFromRow($beforeRow);
        $after = $afterIsValid;

        $skipOrderCheck = ($before === self::IS_VALID_CLOSED && $after === self::IS_VALID_CLOUD_ENABLED);

        if (!$skipOrderCheck && self::requiresOpenNormalOrdersCheck($before, $after)) {
            $n = self::countOpenNormalOrders($companyId, $distributorId);
            if ($n > 0) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorCloudShopStatusGuard.open_orders_block'));
            }
        }

        if ($after !== self::IS_VALID_REVOKED) {
            return;
        }

        $relService = new RegistrationActivityRelShopService();
        $relRows = $relService->entityRepository->getLists(['distributor_id' => $distributorId], 'activity_id', 1, 5000);
        if (!$relRows) {
            return;
        }
        $activityIds = array_values(array_unique(array_filter(array_column($relRows, 'activity_id'))));
        if ($activityIds === []) {
            return;
        }

        $activityRepo = app('registry')->getManager('default')->getRepository(RegistrationActivity::class);
        $activities = $activityRepo->getLists(['company_id' => $companyId, 'activity_id' => $activityIds]);
        $now = time();
        foreach ($activities ?: [] as $act) {
            $end = (int) ($act['end_time'] ?? 0);
            if ($end >= $now) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorCloudShopStatusGuard.revoke_active_registration_activity'));
            }
        }

        $recordRepo = app('registry')->getManager('default')->getRepository(RegistrationRecord::class);
        foreach ($activities ?: [] as $act) {
            $end = (int) ($act['end_time'] ?? 0);
            if ($end >= $now) {
                continue;
            }
            $aid = (int) ($act['activity_id'] ?? 0);
            $cnt = $recordRepo->count([
                'company_id' => $companyId,
                'activity_id' => $aid,
                'status|in' => ['pending', 'passed'],
            ]);
            if ($cnt > 0) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorCloudShopStatusGuard.revoke_open_registration_records'));
            }
        }
    }
}
