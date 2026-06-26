<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Jobs\SyncShopToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMergedJobDispatchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 店铺同步 Job 入队：auth_value 为空时不入队（A-PRE-01）。
 */
final class ShopSyncToShuyunOpenPlatformDispatch
{
    public static function dispatchIfAuthAllows(int $companyId, int $distributorId): void
    {
        $log = ShuyunOpenPlatformShopSyncService::LOG_CHANNEL;
        if ($companyId < 1 || $distributorId < 1) {
            Log::channel($log)->warning(
                'Shuyun open platform shop sync: skip dispatch, invalid company_id or distributor_id.',
                [
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'reason' => 'invalid_ids',
                ]
            );

            return;
        }

        $repo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $row = $repo->findOneByCompanyId($companyId);
        if ($row instanceof CompanyShuyunOpenPlatformConfig && trim((string) ($row->getAuthValue() ?? '')) === '') {
            Log::channel($log)->warning(
                'Shuyun open platform shop sync: skip dispatch, empty auth_value.',
                [
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'reason' => 'empty_auth_value',
                ]
            );

            return;
        }

        $mergeKey = ShuyunOpenPlatformMergedJobDispatchService::shopSyncMergeKey($companyId, $distributorId);
        $didEnqueue = app(ShuyunOpenPlatformMergedJobDispatchService::class)->dispatchUnlessMerged(
            $mergeKey,
            static function () use ($companyId, $distributorId): void {
                app(Dispatcher::class)->dispatch(
                    (new SyncShopToShuyunOpenPlatformJob($companyId, $distributorId))->onQueue('slow')
                );
            },
        );
        if (!$didEnqueue) {
            Log::channel($log)->info(
                'Shuyun open platform shop sync: skip dispatch, merged within TTL (duplicate trigger).',
                [
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                    'reason' => 'merge_dispatch_ttl',
                    'merge_key' => $mergeKey,
                ]
            );

            return;
        }

        Log::channel($log)->info(
            'Shuyun open platform shop sync: job dispatched to slow queue.',
            [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'queue' => 'slow',
                'job' => SyncShopToShuyunOpenPlatformJob::class,
            ]
        );
    }
}
