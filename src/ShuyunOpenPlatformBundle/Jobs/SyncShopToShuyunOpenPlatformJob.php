<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Jobs;

use DistributionBundle\Entities\Distributor;
use DistributionBundle\Repositories\DistributorRepository;
use EspierBundle\Jobs\Job;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShopSyncLifecycleResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncTargetPlatCodesResolver;

/**
 * 店铺写入数云后异步同步（新轨开放网关）。
 *
 * @see .tasks/plans/shuyun-open-platform-shop-sync.md
 * @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md
 */
class SyncShopToShuyunOpenPlatformJob extends Job
{
    /** @var int */
    public $companyId;

    /** @var int */
    public $distributorId;

    public function __construct(int $companyId, int $distributorId)
    {
        $this->companyId = $companyId;
        $this->distributorId = $distributorId;
    }

    public function handle(): bool
    {
        $ch = ShuyunOpenPlatformShopSyncService::LOG_CHANNEL;
        Log::channel($ch)->info('Shuyun open platform shop sync: job started.', [
            'company_id' => $this->companyId,
            'distributor_id' => $this->distributorId,
        ]);

        $em = app('registry')->getManager('default');

        $distRepo = $em->getRepository(Distributor::class);
        if (!$distRepo instanceof DistributorRepository) {
            throw new \RuntimeException('Invalid repository for Distributor.');
        }
        $shop = $distRepo->getInfo([
            'distributor_id' => $this->distributorId,
            'company_id' => $this->companyId,
        ]);
        if ($shop === []) {
            Log::channel($ch)->warning('Shuyun open platform shop sync: job ended, distributor not found.', [
                'company_id' => $this->companyId,
                'distributor_id' => $this->distributorId,
                'reason' => 'distributor_not_found',
            ]);

            return true;
        }

        $lifecycle = app(ShopSyncLifecycleResolver::class)->resolve($shop);

        $configRepo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $openCfg = $configRepo->findOneByCompanyId($this->companyId);
        $targetPlatCodes = app(ShuyunOpenPlatformShopSyncTargetPlatCodesResolver::class)->resolveForShopJob(
            $lifecycle,
            $openCfg,
            $this->companyId,
            $this->distributorId,
            $shop,
        );
        if ($targetPlatCodes === []) {
            Log::channel($ch)->info('Shuyun open platform shop sync: job ended without gateway call.', [
                'company_id' => $this->companyId,
                'distributor_id' => $this->distributorId,
                'reason' => 'empty_target_plat_codes',
            ]);

            return true;
        }

        $svc = app(ShuyunOpenPlatformShopSyncService::class);
        $ok = $svc->syncShop($this->companyId, $shop, $targetPlatCodes);
        if (!$ok) {
            if ($svc->isEligible($openCfg)) {
                throw new \RuntimeException('Shuyun open platform shop sync failed.');
            }

            return true;
        }

        Log::channel($ch)->info('Shuyun open platform shop sync: job finished after gateway sync attempt.', [
            'company_id' => $this->companyId,
            'distributor_id' => $this->distributorId,
            'sync_ok' => true,
        ]);

        return true;
    }
}
