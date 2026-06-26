<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 数云 `shuyun.loyalty.card.grade.query`（GET）query 参数名仍为 `shopId`；
 * 取值与全网关一致：{@see ShuyunOpenPlatformGatewayShopIdResolver}（`distributor_id`）。
 *
 * @see .tasks/plans/shuyun-open-platform-member.md M-GRADE-SHOP-01
 */
final class ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver
{
    private ShuyunOpenPlatformGatewayShopIdResolver $gatewayShopIdResolver;

    public function __construct(ShuyunOpenPlatformGatewayShopIdResolver $gatewayShopIdResolver)
    {
        $this->gatewayShopIdResolver = $gatewayShopIdResolver;
    }

    /**
     * @param  array<string, mixed>  $virtualDistributorRow  distribution_distributor 行（须为虚拟店）
     *
     * @return non-empty-string
     */
    public function resolveShopIdQueryValue(array $virtualDistributorRow): string
    {
        return $this->gatewayShopIdResolver->resolve($virtualDistributorRow);
    }
}
