<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use DistributionBundle\Events\DistributorCreateEvent;

final class DispatchShopSyncToShuyunOpenPlatformOnDistributorCreateListener
{
    public function handle(DistributorCreateEvent $event): void
    {
        $entities = $event->entities;
        $companyId = (int) ($entities['company_id'] ?? 0);
        $distributorId = (int) ($entities['distributor_id'] ?? 0);
        ShopSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows($companyId, $distributorId);
    }
}
