<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace CompanysBundle\Services;

use Dingo\Api\Exception\ResourceException;

/**
 * 邮箱注册前校验：与 POST /mail/setting 写入的 Redis mailSetting 一致，SMTP 必填 + H5 激活域名。
 */
class MailSettingEmailRegistrationValidator
{
    public static function assertReadyForMemberEmailRegistration(int $companyId): void
    {
        $raw = app('redis')->connection('companys')->get('mailSetting:' . $companyId);
        $cfg = json_decode((string) $raw, true);
        if (!is_array($cfg)) {
            $cfg = [];
        }
        $port = trim((string) ($cfg['EMAIL_SMTP_PORT'] ?? ''));
        $host = trim((string) ($cfg['EMAIL_RELAY_HOST'] ?? ''));
        $sender = trim((string) ($cfg['EMAIL_SENDER'] ?? ''));
        $user = trim((string) ($cfg['EMAIL_USER'] ?? ''));
        $password = (string) ($cfg['EMAIL_PASSWORD'] ?? '');
        $h5Domain = (new MailSettingActivationBaseUrlResolver())->getH5ActivationBaseUrl($companyId);

        if ($port === '' || $host === '' || $sender === '' || $user === '' || $password === '' || $h5Domain === '') {
            throw new ResourceException(trans('MembersBundle/Members.email_activation_mail_config_missing'));
        }
        if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            throw new ResourceException(trans('MembersBundle/Members.email_activation_mail_config_missing'));
        }
    }
}
