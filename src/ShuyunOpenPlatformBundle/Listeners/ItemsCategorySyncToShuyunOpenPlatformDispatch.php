<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Jobs\SyncItemsCategoryToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMergedJobDispatchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 商品分类同步 Job 入队：auth_value 为空不入队；同 company+category 在合并窗内去重。
 */
final class ItemsCategorySyncToShuyunOpenPlatformDispatch
{
    public static function dispatchIfAuthAllows(int $companyId, int $categoryId): void
    {
        if ($companyId < 1 || $categoryId < 1) {
            return;
        }

        $repo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $row = $repo->findOneByCompanyId($companyId);
        if ($row instanceof CompanyShuyunOpenPlatformConfig && trim((string) ($row->getAuthValue() ?? '')) === '') {
            Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->warning(
                'Shuyun open platform category sync: skip dispatch, empty auth_value.',
                [
                    'company_id' => $companyId,
                    'category_id' => $categoryId,
                    'reason' => 'empty_auth_value',
                ],
            );

            return;
        }

        $mergeKey = ShuyunOpenPlatformMergedJobDispatchService::categorySyncMergeKey($companyId, $categoryId);
        app(ShuyunOpenPlatformMergedJobDispatchService::class)->dispatchUnlessMerged(
            $mergeKey,
            static function () use ($companyId, $categoryId): void {
                app(Dispatcher::class)->dispatch(
                    (new SyncItemsCategoryToShuyunOpenPlatformJob($companyId, $categoryId))->onQueue('slow')
                );
            },
        );
    }
}
