<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace CompanysBundle\Services;

/**
 * Redis mailSetting 中 EMAIL_PASSWORD 约定为 **明文**（POST /mail/setting 保存前已解密）。
 * 若历史或误操作存入了与 GET 接口一致的密文，发信前尝试解密；失败则按明文使用。
 */
class MailSettingStoredPasswordResolver
{
    public static function plainForSmtp(string $stored): string
    {
        if ($stored === '') {
            return '';
        }
        try {
            return app('fixedencrypt')->default()->decrypt($stored, false);
        } catch (\Throwable $e) {
            return $stored;
        }
    }

    /**
     * @param array<string, mixed> $configMail
     * @return array<string, mixed>
     */
    public static function withResolvedPassword(array $configMail): array
    {
        $raw = isset($configMail['email_password']) ? (string) $configMail['email_password'] : '';
        $configMail['email_password'] = self::plainForSmtp($raw);

        return $configMail;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function redactForLog(array $config): array
    {
        $out = $config;
        if (array_key_exists('EMAIL_PASSWORD', $out)) {
            $out['EMAIL_PASSWORD'] = ($out['EMAIL_PASSWORD'] ?? '') === '' ? '' : '***';
        }
        if (array_key_exists('email_password', $out)) {
            $out['email_password'] = ($out['email_password'] ?? '') === '' ? '' : '***';
        }

        return $out;
    }
}
