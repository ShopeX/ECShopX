<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Jobs;

use EspierBundle\Jobs\Job;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformCategorySyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 商品管理分类（2/3 级）异步同步数云开放网关。
 *
 * @see .tasks/plans/shuyun-open-platform-category-goods-sync.md
 */
class SyncItemsCategoryToShuyunOpenPlatformJob extends Job
{
    /** @var int */
    public $companyId;

    /** @var int */
    public $categoryId;

    public function __construct(int $companyId, int $categoryId)
    {
        $this->companyId = $companyId;
        $this->categoryId = $categoryId;
    }

    public function handle(): bool
    {
        $configRepo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $openCfg = $configRepo->findOneByCompanyId($this->companyId);
        $shopSvc = app(ShuyunOpenPlatformShopSyncService::class);
        $catSvc = app(ShuyunOpenPlatformCategorySyncService::class);

        $ok = $catSvc->syncCategory($this->companyId, $this->categoryId);
        if (! $ok && $shopSvc->isEligible($openCfg)) {
            Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->warning(
                'Shuyun open platform category sync job finished: sync returned false (see prior gateway business failure logs).',
                [
                    'company_id' => $this->companyId,
                    'category_id' => $this->categoryId,
                ]
            );
        }

        return true;
    }
}
