<?php

declare(strict_types=1);

namespace OrdersBundle\Support;

use Dingo\Api\Exception\ResourceException;
use WorkWechatBundle\Services\WorkWechatRelService;

/**
 * Guide order tenant/store scope gate: bind auth company_id and own-store access.
 */
class GuideOrderTenantScopeGuard
{
    /**
     * @param array<string, mixed> $authInfo
     */
    public static function resolveAuthCompanyId(array $authInfo, $requestedCompanyId): int
    {
        $authCompanyId = (int) ($authInfo['company_id'] ?? 0);
        if ($authCompanyId <= 0) {
            throw new ResourceException('企业id缺失');
        }

        if ($requestedCompanyId !== null && $requestedCompanyId !== '' && (int) $requestedCompanyId !== $authCompanyId) {
            throw new ResourceException('无权操作该租户订单');
        }

        return $authCompanyId;
    }

    /**
     * @param array<string, mixed> $authInfo
     */
    public static function resolveGuideDistributorId(array $authInfo, $requestedDistributorId): int
    {
        $ownDistributorId = (int) ($authInfo['distributor_id'] ?? 0);
        if ($ownDistributorId <= 0) {
            throw new ResourceException('请选择店铺');
        }

        if ($requestedDistributorId !== null && $requestedDistributorId !== '' && (int) $requestedDistributorId !== $ownDistributorId) {
            throw new ResourceException('无权操作该店铺订单');
        }

        return $ownDistributorId;
    }

    /**
     * @param array<string, mixed>|null $member
     */
    public static function assertMemberBelongsToCompany(?array $member, int $companyId): void
    {
        if ($companyId <= 0 || !$member || (int) ($member['company_id'] ?? 0) !== $companyId) {
            throw new ResourceException('会员信息有误');
        }
    }

    /**
     * @param array<string, mixed> $orderInfo
     */
    public static function assertOrderInGuideStore(array $orderInfo, int $distributorId): void
    {
        if ($distributorId <= 0) {
            return;
        }

        if ((int) ($orderInfo['distributor_id'] ?? 0) !== $distributorId) {
            throw new ResourceException('此订单不是本店订单');
        }
    }

    /**
     * @param array<string, mixed>|null $rights
     */
    public static function assertRightsBelongsToAuthUser(?array $rights, int $companyId, int $userId): void
    {
        if ($companyId <= 0 || $userId <= 0 || !$rights
            || (int) ($rights['company_id'] ?? 0) !== $companyId
            || (int) ($rights['user_id'] ?? 0) !== $userId) {
            throw new ResourceException('无权访问该权益');
        }
    }

    /**
     * @param array<string, mixed>|null $orderInfo
     */
    public static function assertOrderBelongsToAuthUser(?array $orderInfo, int $companyId, int $userId, ?int $promoterUserId = null): void
    {
        if ($companyId <= 0 || !$orderInfo || (int) ($orderInfo['company_id'] ?? 0) !== $companyId) {
            throw new ResourceException('订单不存在');
        }

        if ($promoterUserId && $promoterUserId > 0) {
            if ((int) ($orderInfo['salesman_id'] ?? 0) !== $promoterUserId) {
                throw new ResourceException('订单用户错误');
            }

            return;
        }

        if ($userId <= 0 || (int) ($orderInfo['user_id'] ?? 0) !== $userId) {
            throw new ResourceException('订单用户错误');
        }
    }

    /**
     * @param array<string, mixed>|null $record
     */
    public static function assertEpidemicRecordBelongsToAuthUser(?array $record, int $companyId, int $userId): void
    {
        if ($companyId <= 0 || $userId <= 0 || !$record
            || (int) ($record['company_id'] ?? 0) !== $companyId
            || (int) ($record['user_id'] ?? 0) !== $userId) {
            throw new ResourceException('无权操作该登记记录');
        }
    }

    /**
     * @param array<string, mixed>|null $voucher
     */
    public static function assertOfflineVoucherBelongsToOrder(?array $voucher, string $orderId, int $companyId): void
    {
        if ($companyId <= 0 || !$voucher
            || (int) ($voucher['company_id'] ?? 0) !== $companyId
            || (string) ($voucher['order_id'] ?? '') !== (string) $orderId) {
            throw new ResourceException('凭证不存在');
        }
    }

    /**
     * @param array<string, mixed>|null $invoice
     */
    public static function assertInvoiceBelongsToAuthUser(?array $invoice, int $companyId, int $userId): void
    {
        if ($companyId <= 0 || $userId <= 0 || !$invoice
            || (int) ($invoice['company_id'] ?? 0) !== $companyId
            || (int) ($invoice['user_id'] ?? 0) !== $userId) {
            throw new ResourceException('无权操作该发票');
        }
    }

    /**
     * @param array<string, mixed> $authInfo
     */
    public static function assertGuideCanAccessMember(array $authInfo, int $memberUserId): void
    {
        if ($memberUserId <= 0) {
            throw new ResourceException('无权访问该会员数据');
        }

        $companyId = (int) ($authInfo['company_id'] ?? 0);
        $salespersonId = (int) ($authInfo['salesperson_id'] ?? 0);
        if ($companyId <= 0 || $salespersonId <= 0) {
            throw new ResourceException('无权访问该会员数据');
        }

        $workWechatRelService = new WorkWechatRelService();
        $rel = $workWechatRelService->getInfo([
            'company_id' => $companyId,
            'salesperson_id' => $salespersonId,
            'user_id' => $memberUserId,
        ]);
        if (empty($rel)) {
            throw new ResourceException('无权访问该会员数据');
        }
    }
}
