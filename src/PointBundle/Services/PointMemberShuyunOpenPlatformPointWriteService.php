<?php

declare(strict_types=1);

namespace PointBundle\Services;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 数云开放网关 point.change：与 PointMemberService::addPoint 编排（先网关后本地）、字段映射（§5.1 / 业务流程 ## 积分）。
 * 扣减：数云为权威时网关成功后不再以本地 point_member 余额为闸门（见 skipsLocalPointMemberBalanceAfterOpenGatewayDeduct）。
 */
final class PointMemberShuyunOpenPlatformPointWriteService
{
    /**
     * 数云为积分权威且本笔为扣减：网关已成功调用后，跳过本地 point_member 的「余额不足」式 UPDATE，仅记流水（剩余以数云端为准）。
     */
    public static function skipsLocalPointMemberBalanceAfterOpenGatewayDeduct(bool $useOpenPlatformWrite, bool $statusIncrease, int $point): bool
    {
        return $useOpenPlatformWrite && !$statusIncrease && $point !== 0;
    }

    public static function isOpenPlatformMemberEnabled(int $companyId): bool
    {
        return self::memberService()->isShuyunOpenPlatformMemberEnabled($companyId);
    }

    public static function assertGatewayEligibleOrThrow(int $companyId): void
    {
        $repo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfg = $repo->findOneByCompanyId($companyId);
        $sync = app(ShuyunOpenPlatformShopSyncService::class);
        if ($cfg === null || !$sync->isEligible($cfg)) {
            throw new ResourceException('数云开放网关未就绪或未完成授权，暂无法调整积分');
        }
    }

    /**
     * @param  array<string, mixed>  $otherParams  addPoint 的 $otherParams；可选 **shuyun_sequence** 覆盖幂等流水号
     * @param  array<string, mixed>  $memberInfo    getMemberInfo 合并结果
     *
     * @return array<string, mixed>
     */
    public static function buildChangePayload(
        int $userId,
        int $companyId,
        int $point,
        bool $status,
        int $journalType,
        string $record,
        string $orderId,
        array $otherParams,
        array $memberInfo
    ): array {
        $reg = (int) ($memberInfo['reg_distributor'] ?? 0);
        if ($reg <= 0) {
            throw new ResourceException('会员缺少注册店铺信息，暂无法调整数云积分');
        }

        $plat = 'OFFLINE';
        $shopId = app(ShuyunOpenPlatformGatewayShopIdResolver::class)
            ->resolve(['distributor_id' => $reg]);

        $sequence = self::resolveSequence($companyId, $userId, $journalType, $status, $point, $orderId, $otherParams);
        $source = self::resolveSource($journalType, $status);
        $operator = self::resolveOperator($userId, $journalType, $status, $orderId);
        $changePoint = $status ? $point : -$point;
        $desc = $record !== '' ? $record : '无记录';

        return [
            'platCode' => strtoupper($plat),
            'id' => (string) $userId,
            'shopId' => $shopId,
            'sequence' => $sequence,
            'created' => date('Y-m-d H:i:s'),
            'source' => $source,
            'changePoint' => $changePoint,
            'operator' => $operator,
            'desc' => $desc,
        ];
    }

    /**
     * 与计划定案一致：取消/退款返还 **REFUND** + 正分；注册/关怀类 **MARKET**；订单抵扣/兑换 **CONSUME**；订单赠送 **TRADE**；其余 **OTHER**。
     */
    public static function resolveSource(int $journalType, bool $status): string
    {
        if ($status) {
            if (in_array($journalType, [9, 10], true)) {
                return 'REFUND';
            }
            if (in_array($journalType, [1, 2, 16], true)) {
                return 'MARKET';
            }
            if ($journalType === 7) {
                return 'TRADE';
            }
            // 开放接口同步：增加为 OTHER，扣减走 !$status 分支 CONSUME
            if ($journalType === 13) {
                return 'OTHER';
            }

            return 'OTHER';
        }

        if (in_array($journalType, [5, 6], true)) {
            return 'CONSUME';
        }

        return 'CONSUME';
    }

    /**
     * 会员小程序下单类扣减（积分抵扣、积分换购等带订单号的扣减）→ operator = user_id；其余 **system**。
     */
    public static function resolveOperator(int $userId, int $journalType, bool $status, string $orderId): string
    {
        if (!$status && $orderId !== '' && in_array($journalType, [5, 6], true)) {
            return (string) $userId;
        }

        return 'system';
    }

    /**
     * @param  array<string, mixed>  $otherParams
     */
    private static function resolveSequence(
        int $companyId,
        int $userId,
        int $journalType,
        bool $status,
        int $point,
        string $orderId,
        array $otherParams
    ): string {
        $forced = trim((string) ($otherParams['shuyun_sequence'] ?? ''));
        if ($forced !== '') {
            return $forced;
        }

        $ext = (string) ($otherParams['external_id'] ?? '');
        $pointType = (string) ($otherParams['point_type'] ?? '');

        return 'sxop_'.substr(hash('sha256', implode('|', [
            (string) $companyId,
            (string) $userId,
            (string) $journalType,
            $orderId,
            $ext,
            $pointType,
            $status ? 'add' : 'sub',
            (string) $point,
        ])), 0, 48);
    }

    private static function memberService(): MemberService
    {
        return app(MemberService::class);
    }
}
