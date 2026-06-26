<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Jobs;

use EspierBundle\Jobs\Job;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformProductSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 店铺维度 SPU（default_item_id）异步同步至 {@see ShuyunOpenPlatformProductSyncService::GATEWAY_ACTION_PRODUCT_SYNC}。
 *
 * @see .tasks/plans/shuyun-open-platform-category-goods-sync.md A-PROD-01、A-PROD-02
 */
class SyncProductsToShuyunOpenPlatformJob extends Job
{
    /** @var int */
    public $companyId;

    /** @var int */
    public $distributorId;

    /** @var int */
    public $defaultItemId;

    public function __construct(int $companyId, int $distributorId, int $defaultItemId)
    {
        $this->companyId = $companyId;
        $this->distributorId = $distributorId;
        $this->defaultItemId = $defaultItemId;
    }

    public function handle(): bool
    {
        $configRepo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $openCfg = $configRepo->findOneByCompanyId($this->companyId);
        $shopSvc = app(ShuyunOpenPlatformShopSyncService::class);
        $productSvc = app(ShuyunOpenPlatformProductSyncService::class);

        $ok = $productSvc->syncProductByDefaultItem($this->companyId, $this->distributorId, $this->defaultItemId);
        if (! $ok && $shopSvc->isEligible($openCfg)) {
            // 网关业务拒绝等已在 ProductSyncService / 数云网关日志中记录；勿抛异常以免队列无意义重试与 production.ERROR 噪音。
            Log::channel(ShuyunOpenPlatformShopSyncService::LOG_CHANNEL)->warning(
                'Shuyun open platform product sync job finished: sync returned false (see prior gateway business failure logs).',
                [
                    'company_id' => $this->companyId,
                    'distributor_id' => $this->distributorId,
                    'default_item_id' => $this->defaultItemId,
                ]
            );
        }

        return true;
    }
}
