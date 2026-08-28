<?php

declare(strict_types=1);

namespace CompanysBundle\Support;

use CompanysBundle\Entities\WxShops;
use Dingo\Api\Exception\ResourceException;

/**
 * WxShop tenant-scope gate: bind auth company_id on shop writes.
 */
class WxShopTenantScopeGuard
{
    public static function assertWxShopBelongsToCompany(int $wxShopId, int $companyId): void
    {
        if ($wxShopId <= 0 || $companyId <= 0) {
            throw new ResourceException('无权操作该门店');
        }

        $repository = app('registry')->getManager('default')->getRepository(WxShops::class);
        $shop = $repository->findOneBy([
            'wx_shop_id' => $wxShopId,
            'company_id' => $companyId,
        ]);
        if (!$shop) {
            throw new ResourceException('无权操作该门店');
        }
    }
}
