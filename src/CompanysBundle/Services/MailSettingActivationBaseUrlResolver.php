<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace CompanysBundle\Services;

/**
 * Redis mailSetting:{companyId} 中租户级「邮箱激活」根地址（与 POST /mail/setting 字段一致）。
 */
class MailSettingActivationBaseUrlResolver
{
    public const KEY_H5 = 'EMAIL_ACTIVATION_H5_DOMAIN';

    public const KEY_PC = 'EMAIL_ACTIVATION_PC_DOMAIN';

    public static function sanitizeStoredDomain(?string $value): string
    {
        $s = str_replace(["\r", "\n"], '', trim((string) $value));
        if (strlen($s) > 512) {
            $s = substr($s, 0, 512);
        }

        return $s;
    }

    public function getH5ActivationBaseUrl(int $companyId): string
    {
        return self::sanitizeStoredDomain($this->readField($companyId, self::KEY_H5));
    }

    public function getPcActivationBaseUrl(int $companyId): string
    {
        return self::sanitizeStoredDomain($this->readField($companyId, self::KEY_PC));
    }

    private function readField(int $companyId, string $key): string
    {
        $raw = app('redis')->connection('companys')->get('mailSetting:' . $companyId);
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || !isset($decoded[$key])) {
            return '';
        }

        return (string) $decoded[$key];
    }
}
