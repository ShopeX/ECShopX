<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace MembersBundle\Services;

use CompanysBundle\Services\CompanySettingBrandNameService;
use CompanysBundle\Services\MailerService;
use Dingo\Api\Exception\ResourceException;

/**
 * 邮箱激活：一次性链接 token（DB 存哈希），与密码重置 token 分表。
 */
class MemberEmailActivationService
{
    /** H5 激活落地页路径（相对 activationBaseUrl 根，与 uni-app subpages 对齐） */
    public const ACTIVATION_EMAIL_PATH = '/subpages/auth/email-activate';

    public function invalidatePendingForUser(int $companyId, int $userId): void
    {
        $conn = app('registry')->getConnection('default');
        $conn->executeUpdate(
            'DELETE FROM member_email_activation_tokens WHERE company_id = ? AND user_id = ? AND used_at IS NULL',
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
        $ttl = (int) (config('common.member_email_activation_token_ttl') ?: 172800);
        $expires = time() + $ttl;
        $conn = app('registry')->getConnection('default');
        $conn->insert('member_email_activation_tokens', [
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
            'SELECT user_id, company_id, expires_at, used_at FROM member_email_activation_tokens WHERE company_id = ? AND token_hash = ?',
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

    public function consumeToken(int $companyId, string $plainToken): void
    {
        $hash = hash('sha256', $plainToken);
        $conn = app('registry')->getConnection('default');
        $conn->executeUpdate(
            'UPDATE member_email_activation_tokens SET used_at = ? WHERE company_id = ? AND token_hash = ? AND used_at IS NULL',
            [time(), $companyId, $hash]
        );
    }

    public function sendActivationEmail(int $companyId, string $email, string $activationUrlWithToken): void
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
        $brand = (new CompanySettingBrandNameService())->resolveForCompanyId($companyId);
        $subject = trans('MembersBundle/Members.email_activation_subject');
        $body = view('members.email_activation', [
            'brand' => $brand,
            'activationUrl' => $activationUrlWithToken,
        ])->render();
        $mailer = new MailerService($configMail);
        if (!$mailer->doSend($email, $subject, $body)) {
            throw new ResourceException(trans('MembersBundle/Members.email_send_failed'));
        }
    }

    /**
     * 向未激活会员发送含激活链接的邮件（注册成功 / purpose=activate 重发）。
     */
    public function sendActivationLinkEmail(
        int $companyId,
        string $email,
        string $clientIp,
        ?string $deviceId,
        string $activationBaseUrl
    ): void {
        $emailSvc = new MemberEmailVerificationService();
        $normalized = $emailSvc->normalizeEmail($email);
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new ResourceException(trans('MembersBundle/Members.invalid_email'));
        }
        $repo = app('registry')->getManager('default')->getRepository(\MembersBundle\Entities\Members::class);
        $member = $repo->findOneBy(['company_id' => $companyId, 'login_email' => $normalized]);
        if (!$member || $member->getEmailVerifiedAt()) {
            throw new ResourceException(trans('MembersBundle/Members.email_activation_send_not_allowed'));
        }
        $base = rtrim($activationBaseUrl, '/');
        if ($base === '') {
            throw new ResourceException(trans('MembersBundle/Members.email_activation_base_url_required'));
        }
        $emailSvc->assertCanSendEmailPurpose($companyId, MemberEmailVerificationService::PURPOSE_ACTIVATE, $normalized, $clientIp, $deviceId);
        $userId = (int) $member->getUserId();
        $plain = $this->createToken($companyId, $userId);
        $url = $base . self::ACTIVATION_EMAIL_PATH . '?token=' . rawurlencode($plain) . '&company_id=' . $companyId;
        $this->sendActivationEmail($companyId, $normalized, $url);
        $emailSvc->setPurposeSendCooldown($companyId, MemberEmailVerificationService::PURPOSE_ACTIVATE, $normalized);
    }
}
