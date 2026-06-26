<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use DistributionBundle\Events\DistributorUpdateEvent;

final class DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListener
{
    public function handle(DistributorUpdateEvent $event): void
    {
        $entities = $event->entities;
        $companyId = (int) ($entities['company_id'] ?? 0);
        $distributorId = (int) ($entities['distributor_id'] ?? 0);
        $name = (string) ($entities['name'] ?? '');
        $oldName = (string) ($entities['__old_name'] ?? '');
        $oldIsValid = $this->normalizeState($entities['__old_is_valid'] ?? null);
        $newIsValid = $this->normalizeState($entities['is_valid'] ?? null);

        $statusIntent = $this->toBool($entities['__client_intent_status'] ?? false);
        $profileIntent = $this->toBool($entities['__client_intent_profile'] ?? false);
        $isVirtualShop = trim((string) ($entities['distributor_self'] ?? '')) === '1';

        $nameChanged = $name !== '' && $name !== $oldName;
        $statusChanged = $oldIsValid !== '' && $newIsValid !== '' && $oldIsValid !== $newIsValid;
        $statusShouldDispatch = $statusIntent && !$isVirtualShop && $statusChanged;

        if (!$nameChanged && !$profileIntent && !$statusShouldDispatch) {
            return;
        }
        ShopSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows($companyId, $distributorId);
    }

    private function normalizeState(mixed $v): string
    {
        return strtolower(trim((string) $v));
    }

    private function toBool(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        $s = strtolower(trim((string) $v));

        return in_array($s, ['1', 'true', 'yes', 'on'], true);
    }
}
