<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Exception\ShuyunOpenPlatformManageConfigGateException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * T5：管理端读写。冷启动阶段可在无 platCode 时保存 app 凭据（须 env auth_value 或库内 auth_value 供 token 回调匹配）；
 * 开启 is_enabled 前须有有效 access_token；plat_code 规范为 OFFLINE（入站方案 A）。仅 patch access_token 时仍只要求 app_id+app_secret。
 */
final class ShuyunOpenPlatformManageConfigService
{
    /** @var list<string> */
    private const ACCESS_TOKEN_PATCH_KEYS = ['access_token', 'enabled', 'is_enabled'];

    /** @var list<string> */
    private const CREDENTIAL_BOOTSTRAP_KEYS = ['app_id', 'app_secret', 'enabled', 'is_enabled'];

    /** @var list<string> */
    private const SYNC_ENABLE_ONLY_KEYS = ['enabled', 'is_enabled'];

    private const OFFLINE_PLAT_CODE = 'OFFLINE';

    private CompanyShuyunOpenPlatformConfigRepository $repository;

    public function __construct(CompanyShuyunOpenPlatformConfigRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAdminView(int $companyId): ?array
    {
        $row = $this->repository->findOneByCompanyId($companyId);
        if ($row === null) {
            return null;
        }

        return [
            'company_id' => $row->getCompanyId(),
            'plat_code' => $row->getPlatCode(),
            'app_id' => $row->getAppId(),
            'app_secret_masked' => $this->maskSecret($row->getAppSecret()),
            'access_token' => $row->getAccessToken(),
            'is_enabled' => $row->getIsEnabled() === 1,
            'is_over_due' => $row->getIsOverDue(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveFromAdmin(int $companyId, array $input): void
    {
        $row = $this->repository->findOneByCompanyId($companyId);
        if ($this->isAccessTokenOnlyPatch($input)) {
            $this->assertManualAccessTokenGate($row);
        } elseif ($this->isSyncEnableOnlyPatch($input)) {
            if ($row === null) {
                throw new ShuyunOpenPlatformManageConfigGateException(
                    '无配置记录，无法变更启用状态；请先保存应用凭据。',
                );
            }
        } elseif ($this->isCredentialBootstrapPatch($input)) {
            if ($row === null) {
                $row = $this->newRowForCompany($companyId);
            }
        } else {
            $this->assertManageGate($row);
        }

        if ($row === null) {
            throw new ShuyunOpenPlatformManageConfigGateException(
                '无配置记录，请先保存应用凭据。',
            );
        }

        if (array_key_exists('app_id', $input)) {
            $v = $input['app_id'];
            if ($v !== null && $v !== '') {
                $row->setAppId((string) $v);
            }
        }
        if (array_key_exists('app_secret', $input)) {
            $v = $input['app_secret'];
            if ($v !== null && (string) $v !== '') {
                $row->setAppSecret((string) $v);
            }
        }
        $this->applyAuthValueFromConfigIfEmpty($row);
        if (array_key_exists('access_token', $input)) {
            $v = $input['access_token'];
            if ($v !== null && (string) $v !== '') {
                $row->setAccessToken((string) $v);
                $row->setIsOverDue('0');
            } else {
                $row->setAccessToken(null);
            }
        }

        if (array_key_exists('enabled', $input)) {
            if ($this->toBool($input['enabled'])) {
                $this->assertEnableGate($row);
                $row->setIsEnabled(1);
            } else {
                $row->setIsEnabled(0);
            }
        } elseif (array_key_exists('is_enabled', $input)) {
            if ((bool) $input['is_enabled']) {
                $this->assertEnableGate($row);
            }
            $row->setIsEnabled((int) ((bool) $input['is_enabled']));
        }

        $this->ensureOfflinePlatCode($row);
        $this->repository->save($row);
    }

    private function newRowForCompany(int $companyId): CompanyShuyunOpenPlatformConfig
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId($companyId);
        $row->setIsEnabled(0);

        return $row;
    }

    private function applyAuthValueFromConfigIfEmpty(CompanyShuyunOpenPlatformConfig $row): void
    {
        $existing = $row->getAuthValue();
        if ($existing !== null && $existing !== '') {
            return;
        }
        try {
            if (!\function_exists('app')) {
                return;
            }
            $app = \app();
            if (!$app->bound('config')) {
                return;
            }
            $authValue = trim((string) config('shuyun_open_platform.auth_value', ''));
            if ($authValue !== '') {
                $row->setAuthValue($authValue);
            }
        } catch (\Throwable $e) {
        }
    }

    private function assertManageGate(?CompanyShuyunOpenPlatformConfig $row): void
    {
        if ($row === null) {
            throw new ShuyunOpenPlatformManageConfigGateException(
                '无配置记录，请先保存应用凭据。',
            );
        }
    }

    private function assertEnableGate(CompanyShuyunOpenPlatformConfig $row): void
    {
        $token = $row->getAccessToken();
        if ($token === null || $token === '') {
            throw new ShuyunOpenPlatformManageConfigGateException(
                '须先获取有效 access_token 后再开启数云同步。',
            );
        }
        if ($row->getIsOverDue() === '1') {
            throw new ShuyunOpenPlatformManageConfigGateException(
                'access_token 已过期，请先刷新 token 后再开启数云同步。',
            );
        }
    }

    /**
     * 仅改 access_token：不要求 platCode，但必须已有 app_id+app_secret（与数云应用一致，供网关调用与验签）。
     */
    private function assertManualAccessTokenGate(?CompanyShuyunOpenPlatformConfig $row): void
    {
        if ($row === null) {
            throw new ShuyunOpenPlatformManageConfigGateException(
                '无配置记录，无法写入 access_token；请先保存应用凭据或在库中建立 company 配置行。',
            );
        }
        $appId = $row->getAppId();
        $secret = $row->getAppSecret();
        if ($appId === null || $appId === '' || $secret === null || $secret === '') {
            throw new ShuyunOpenPlatformManageConfigGateException(
                '库中缺少 app_id 或 app_secret，无法写入 access_token；请先保存应用凭据。',
            );
        }
    }

    private function ensureOfflinePlatCode(CompanyShuyunOpenPlatformConfig $row): void
    {
        $row->setPlatCode(self::OFFLINE_PLAT_CODE);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function isSyncEnableOnlyPatch(array $input): bool
    {
        if ($input === []) {
            return false;
        }
        if (!array_key_exists('enabled', $input) && !array_key_exists('is_enabled', $input)) {
            return false;
        }
        foreach (array_keys($input) as $key) {
            if (!in_array($key, self::SYNC_ENABLE_ONLY_KEYS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function isAccessTokenOnlyPatch(array $input): bool
    {
        if ($input === [] || !array_key_exists('access_token', $input)) {
            return false;
        }
        foreach (array_keys($input) as $key) {
            if (!in_array($key, self::ACCESS_TOKEN_PATCH_KEYS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 冷启动：仅提交 app_id / app_secret（及可选 enabled），不要求 platCode。
     *
     * @param  array<string, mixed>  $input
     */
    private function isCredentialBootstrapPatch(array $input): bool
    {
        if ($input === []) {
            return false;
        }
        if (!array_key_exists('app_id', $input) && !array_key_exists('app_secret', $input)) {
            return false;
        }
        foreach (array_keys($input) as $key) {
            if (!in_array($key, self::CREDENTIAL_BOOTSTRAP_KEYS, true)) {
                return false;
            }
        }

        return true;
    }

    private function maskSecret(?string $secret): string
    {
        if ($secret === null || $secret === '') {
            return '';
        }
        $len = strlen($secret);
        if ($len <= 4) {
            return '****';
        }

        return '****'.substr($secret, -4);
    }

    private function toBool(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v)) {
            return $v === 1;
        }
        if (is_string($v)) {
            return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $v;
    }
}
