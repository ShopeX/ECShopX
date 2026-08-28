<?php

declare(strict_types=1);

namespace PromotionsBundle\Support;

use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Entities\BargainPromotions;
use PromotionsBundle\Entities\LuckyDrawActivity;

/**
 * Promotion activity tenant-scope gate: bind auth company_id on activity reads/writes.
 */
class PromotionActivityTenantScopeGuard
{
    public static function assertLuckyDrawActivityBelongsToCompany(int $activityId, int $companyId): void
    {
        if ($activityId <= 0 || $companyId <= 0) {
            throw new ResourceException('无权访问该活动');
        }

        $repository = app('registry')->getManager('default')->getRepository(LuckyDrawActivity::class);
        $activity = $repository->getInfo(['id' => $activityId, 'company_id' => $companyId]);
        if (!$activity) {
            throw new ResourceException('无权访问该活动');
        }
    }

    public static function assertBargainPromotionBelongsToCompany(int $bargainId, int $companyId): void
    {
        if ($bargainId <= 0 || $companyId <= 0) {
            throw new ResourceException('无权访问该活动');
        }

        $repository = app('registry')->getManager('default')->getRepository(BargainPromotions::class);
        $bargain = $repository->getInfo(['bargain_id' => $bargainId, 'company_id' => $companyId]);
        if (!$bargain) {
            throw new ResourceException('无权访问该活动');
        }
    }
}
