<?php

declare(strict_types=1);

namespace SalespersonBundle\Support;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Entities\MemberRelGroup;
use MembersBundle\Entities\Members;
use WorkWechatBundle\Services\WorkWechatRelService;

/**
 * 导购会员 scope：发券等操作仅允许作用于本导购可见会员。
 */
class SalespersonMemberScopeGuard
{
    /**
     * @param array<string, mixed> $salespersonInfo
     * @param int[]|string[] $userIds
     */
    public static function assertSalespersonCanGrantToUsers(array $salespersonInfo, array $userIds): void
    {
        foreach ($userIds as $userId) {
            self::assertSalespersonCanAccessMember($salespersonInfo, (int) $userId);
        }
    }

    /**
     * @param array<string, mixed> $salespersonInfo
     */
    public static function assertSalespersonCanAccessMember(array $salespersonInfo, int $memberUserId): void
    {
        if ($memberUserId <= 0) {
            throw new ResourceException('无权向该会员发券');
        }

        $companyId = (int) ($salespersonInfo['company_id'] ?? 0);
        $salespersonId = (int) ($salespersonInfo['salesperson_id'] ?? 0);
        if ($companyId <= 0 || $salespersonId <= 0) {
            throw new ResourceException('无权向该会员发券');
        }

        $membersRepo = app('registry')->getManager('default')->getRepository(Members::class);
        $member = $membersRepo->get(['company_id' => $companyId, 'user_id' => $memberUserId]);
        if (!$member) {
            throw new ResourceException('无权向该会员发券');
        }

        $workWechatRelService = new WorkWechatRelService();
        $rel = $workWechatRelService->getInfo([
            'company_id' => $companyId,
            'salesperson_id' => $salespersonId,
            'user_id' => $memberUserId,
        ]);
        if (!empty($rel)) {
            return;
        }

        $groupRepo = app('registry')->getManager('default')->getRepository(MemberRelGroup::class);
        $groupRel = $groupRepo->getInfo([
            'company_id' => $companyId,
            'salesperson_id' => $salespersonId,
            'user_id' => $memberUserId,
        ]);
        if (!empty($groupRel)) {
            return;
        }

        throw new ResourceException('无权向该会员发券');
    }
}
