<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace MembersBundle\Services;

use CompanysBundle\Services\CompanySettingBrandNameService;
use CompanysBundle\Services\MailerService;
use Dingo\Api\Exception\ResourceException;

class MemberPasswordResetService
{
    public function invalidatePendingForUser(int $companyId, int $userId): void
    {
        $conn = app('registry')->getConnection('default');
        $conn->executeUpdate(
            'DELETE FROM member_password_reset_tokens WHERE company_id = ? AND user_id = ? AND used_at IS NULL',
            [$companyId, $userId]
        );
    }

    /**
     * @return string 明文 token（放入邮件链接）
     */
    public function createToken(int $companyId, int $userId): string
    {
        $this->invalidatePendingForUser($companyId, $userId);
        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);
        $ttl = (int) (config('common.member_email_reset_token_ttl') ?: 1200);
        $expires = time() + $ttl;
        $conn = app('registry')->getConnection('default');
        $conn->insert('member_password_reset_tokens', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => $expires,
            'used_at' => null,
            'created_at' => time(),
        ]);

        return $plain;
    }

    /**
     * @return array{user_id:int, company_id:int}|null
     */
    public function validateToken(int $companyId, string $plainToken): ?array
    {
        $hash = hash('sha256', $plainToken);
        $conn = app('registry')->getConnection('default');
        $row = $conn->fetchAssoc(
            'SELECT user_id, company_id, expires_at, used_at FROM member_password_reset_tokens WHERE company_id = ? AND token_hash = ?',
            [$companyId, $hash]
        );
        if (!$row) {
            return null;
        }
        if (!empty($row['used_at'])) {
            return null;
        }
        if ((int) $row['expires_at'] < time()) {
            return null;
        }

        return ['user_id' => (int) $row['user_id'], 'company_id' => (int) $row['company_id']];
    }

    /**
     * 邮件内 href 仅允许 http(s) 绝对 URL，防止 javascript: 等协议。
     */
    public static function isAllowedResetEmailUrl(string $url): bool
    {
        $trim = trim($url);
        if ($trim === '') {
            return false;
        }
        $parts = @parse_url($trim);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        $scheme = strtolower((string) $parts['scheme']);

        return $scheme === 'http' || $scheme === 'https';
    }

    public function consumeToken(int $companyId, string $plainToken): void
    {
        $hash = hash('sha256', $plainToken);
        $conn = app('registry')->getConnection('default');
        $conn->executeUpdate(
            'UPDATE member_password_reset_tokens SET used_at = ? WHERE company_id = ? AND token_hash = ? AND used_at IS NULL',
            [time(), $companyId, $hash]
        );
    }

    public function sendResetEmail(int $companyId, string $email, string $resetUrlWithToken): void
    {
        $config = app('redis')->connection('companys')->get('mailSetting:' . $companyId);
        $config = json_decode((string) $config, true);
        if (empty($config['EMAIL_SMTP_PORT']) || empty($config['EMAIL_RELAY_HOST'])) {
            throw new ResourceException(trans('MembersBundle/Members.company_mail_not_configured'));
        }
        $configMail = [
            'email_smtp_port' => $config['EMAIL_SMTP_PORT'],
            'email_relay_host' => $config['EMAIL_RELAY_HOST'],
            'email_user' => $config['EMAIL_USER'],
            'email_password' => (string) ($config['EMAIL_PASSWORD'] ?? ''),
            'email_sender' => $config['EMAIL_SENDER'],
        ];
        if (!self::isAllowedResetEmailUrl($resetUrlWithToken)) {
            throw new ResourceException(trans('MembersBundle/Members.email_password_reset_link_invalid'));
        }
        $brand = (new CompanySettingBrandNameService())->resolveForCompanyId($companyId);
        $ttlSeconds = (int) (config('common.member_email_reset_token_ttl') ?: 1200);
        $ttlMinutes = max(1, (int) ceil($ttlSeconds / 60));
        $subject = trans('MembersBundle/Members.email_password_reset_subject');
        $body = view('members.email_password_reset', [
            'brand' => $brand,
            'resetUrl' => $resetUrlWithToken,
            'ttlMinutes' => $ttlMinutes,
        ])->render();
        $mailer = new MailerService($configMail);
        if (!$mailer->doSend($email, $subject, $body)) {
            throw new ResourceException(trans('MembersBundle/Members.email_send_failed'));
        }
    }
}
