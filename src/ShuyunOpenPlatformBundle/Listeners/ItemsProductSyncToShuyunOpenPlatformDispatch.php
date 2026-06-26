<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Jobs\SyncProductsToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMergedJobDispatchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 商品同步 Job 入队：auth_value 空跳过；merge key = company + distributor + default_item_id。
 */
final class ItemsProductSyncToShuyunOpenPlatformDispatch
{
    public static function dispatchIfAuthAllows(int $companyId, int $distributorId, int $defaultItemId): void
    {
        if ($companyId < 1 || $distributorId < 1 || $defaultItemId < 1) {
            return;
        }

        $repo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $row = $repo->findOneByCompanyId($companyId);
        if ($row instanceof CompanyShuyunOpenPlatformConfig && trim((string) ($row->getAuthValue() ?? '')) === '') {
            Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->warning(
                'Shuyun open platform product sync: skip dispatch, empty auth_value.',
                [
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'default_item_id' => $defaultItemId,
                    'reason' => 'empty_auth_value',
                ],
            );

            return;
        }

        $mergeKey = ShuyunOpenPlatformMergedJobDispatchService::productSyncMergeKey($companyId, $distributorId, $defaultItemId);
        app(ShuyunOpenPlatformMergedJobDispatchService::class)->dispatchUnlessMerged(
            $mergeKey,
            static function () use ($companyId, $distributorId, $defaultItemId): void {
                app(Dispatcher::class)->dispatch(
                    (new SyncProductsToShuyunOpenPlatformJob($companyId, $distributorId, $defaultItemId))->onQueue('slow')
                );
            },
        );
    }
}
