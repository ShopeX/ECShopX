<?php

declare(strict_types=1);

namespace PaymentBundle\Services;

use DistributionBundle\Services\DistributorService;
use Dingo\Api\Exception\ResourceException;

/**
 * Payment / AdaPay / BsPay 租户与店级归属校验。
 */
class PaymentTenantScopeGuard
{
    /**
     * @param array<string, mixed> $row
     */
    public static function assertCompanyScope(array $row, int $companyId): void
    {
        if ($companyId <= 0 || empty($row) || (int) ($row['company_id'] ?? 0) !== $companyId) {
            throw new ResourceException('无权访问该资源');
        }
    }

  /**
     * 支付设置读写：店级 operator 强制本店；总部可指定本租户 distributor。
     *
     * @param object $user auth user（须支持 get）
     */
    public static function resolvePaymentDistributorId($user, int $requestedDistributorId): int
    {
        $companyId = (int) $user->get('company_id');
        $operatorType = (string) $user->get('operator_type');

        if ($operatorType === 'distributor') {
            $ownDistributorId = (int) $user->get('distributor_id');
            if ($ownDistributorId <= 0) {
                throw new ResourceException('请选择店铺');
            }
            if ($requestedDistributorId > 0 && $requestedDistributorId !== $ownDistributorId) {
                throw new ResourceException('无权访问该店铺配置');
            }

            return $ownDistributorId;
        }

        if ($requestedDistributorId <= 0) {
            return 0;
        }

        $distributorService = new DistributorService();
        $distributor = $distributorService->getInfo([
            'distributor_id' => $requestedDistributorId,
            'company_id' => $companyId,
        ]);
        if (!$distributor) {
            throw new ResourceException('无权访问该店铺配置');
        }

        return $requestedDistributorId;
    }

    /**
     * Store-scoped operators must not widen scope via distributor_name filter.
     *
     * @param object $user auth user（须支持 get）
     */
    public static function assertDistributorNameFilterAllowed($user): void
    {
        if ((string) $user->get('operator_type') === 'distributor') {
            throw new ResourceException('无权筛选其他店铺');
        }
    }

    /**
     * @param object $user auth user（须支持 get）
     */
    public static function assertOperatorInCompany($user, int $operatorId): void
    {
        $companyId = (int) $user->get('company_id');
        if ($companyId <= 0 || $operatorId <= 0) {
            throw new ResourceException('无权操作该账号');
        }

        $operatorsService = new \CompanysBundle\Services\OperatorsService();
        $info = $operatorsService->getInfo([
            'company_id' => $companyId,
            'operator_id' => $operatorId,
        ]);
        if (!$info) {
            throw new ResourceException('无权操作该账号');
        }
    }
}
