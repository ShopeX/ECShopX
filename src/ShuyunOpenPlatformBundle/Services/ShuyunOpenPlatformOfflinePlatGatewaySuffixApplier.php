<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 历史 OFFLINE 出站名/ID 后缀工具类；**业务同步已不再调用**（shuyun-offline-only）。
 * 仅保留供单测或排障时显式 `config([...])` 注入后缀规则。
 *
 * @internal
 */
final class ShuyunOpenPlatformOfflinePlatGatewaySuffixApplier
{
    public static function applyDisplayNameSuffix(string $name): string
    {
        $name = trim($name);
        $suffix = trim((string) config('shuyun_open_platform.offline_plat_name_suffix', ''));
        if ($name === '' || $suffix === '') {
            return $name;
        }
        if (str_ends_with($name, $suffix)) {
            return $name;
        }

        return $name.$suffix;
    }

    public static function applyExternalIdSuffix(string $externalId): string
    {
        $externalId = trim($externalId);
        $suffix = trim((string) config('shuyun_open_platform.offline_plat_id_suffix', ''));
        if ($externalId === '' || $suffix === '') {
            return $externalId;
        }
        if (str_ends_with($externalId, $suffix)) {
            return $externalId;
        }

        return $externalId.$suffix;
    }
}
