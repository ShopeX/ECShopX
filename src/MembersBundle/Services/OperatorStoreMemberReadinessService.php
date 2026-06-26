<?php

declare(strict_types=1);

namespace MembersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberRegisterService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberSyncState;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 移动收银：在拉取购物车前，将会员登记到当前门店对应的开放平台线下侧（与店务建档后同步语义一致）。
 * OFFLINE-only：若 {@see Members::$offline_reg_distributor} 或 {@see Members::$shuyun_open_online_wxapp_sync_at} 表明已入会，则不再 register（与本次门店 id 无关）。
 */
final class OperatorStoreMemberReadinessService
{
    public function __construct(
        private CompanyShuyunOpenPlatformConfigRepository $configRepository,
        private ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        private ShuyunOpenPlatformMemberRegisterService $memberRegisterService,
        private MemberService $memberService,
    ) {
    }

    /**
     * @return array{synced: bool, skipped: bool}
     */
    public function ensureOfflineMemberAtStore(int $companyId, int $distributorId, int $userId): array
    {
        if ($userId <= 0) {
            throw new ResourceException(trans('MembersBundle/Members.store_member_ready_user_required'));
        }
        if ($distributorId <= 0) {
            throw new ResourceException(trans('MembersBundle/Members.store_member_ready_distributor_required'));
        }

        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || (int) $config->getIsEnabled() !== 1) {
            return ['synced' => false, 'skipped' => true];
        }
        if (! $this->shopSyncEligibility->isEligible($config)) {
            return ['synced' => false, 'skipped' => true];
        }

        $memberRow = $this->memberService->getMemberInfo([
            'company_id' => $companyId,
            'user_id' => $userId,
        ]);
        if (! \is_array($memberRow) || ! isset($memberRow['user_id'], $memberRow['company_id'])) {
            throw new ResourceException(trans('MembersBundle/Members.store_member_ready_not_found'));
        }
        if ((int) $memberRow['company_id'] !== $companyId) {
            throw new ResourceException(trans('MembersBundle/Members.store_member_ready_not_found'));
        }

        if (ShuyunOpenPlatformMemberSyncState::isRegisteredWithOpenPlatform($memberRow)) {
            return ['synced' => false, 'skipped' => true];
        }

        $mobile = trim((string) ($memberRow['mobile'] ?? ''));
        if ($mobile === '') {
            throw new ResourceException(trans('MembersBundle/Members.store_member_ready_mobile_missing'));
        }

        $distributorRow = ['distributor_id' => $distributorId];
        $shuyunOpenMemberRegisterSucceeded = false;

        try {
            $this->memberRegisterService->registerSingle(
                $companyId,
                $distributorRow,
                (string) $userId,
                $mobile,
                null,
                null,
                true
            );
            $shuyunOpenMemberRegisterSucceeded = true;
            $this->memberService->syncUserCardCodeFromShuyunEnhanceAfterRegister(
                $companyId,
                $userId,
                $distributorRow,
                true
            );
        } catch (\Throwable $e) {
            app('log')->warning('Operator store member readiness: open platform member.register failed.', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'distributor_id' => $distributorId,
                'exception' => \get_class($e),
                'message' => $e->getMessage(),
                'shuyun_open_member_register_succeeded' => $shuyunOpenMemberRegisterSucceeded,
            ]);
            throw new ResourceException(trans('MembersBundle/Members.store_member_ready_sync_failed', [
                'msg' => $e->getMessage(),
            ]));
        }

        try {
            $this->memberService->updateMemberInfo(
                ['offline_reg_distributor' => $distributorId],
                ['company_id' => $companyId, 'user_id' => $userId]
            );
        } catch (\Throwable $persistEx) {
            app('log')->error('Operator store member readiness: failed to persist offline_reg_distributor.', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'distributor_id' => $distributorId,
                'exception' => \get_class($persistEx),
                'message' => $persistEx->getMessage(),
            ]);
            throw new ResourceException(trans('MembersBundle/Members.store_member_ready_sync_failed', [
                'msg' => $persistEx->getMessage(),
            ]));
        }

        return ['synced' => true, 'skipped' => false];
    }
}
