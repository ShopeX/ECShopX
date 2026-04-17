<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace MembersBundle\Services;

use CompanysBundle\Services\CompanySettingBrandNameService;
use CompanysBundle\Services\MailerService;
use Dingo\Api\Exception\ResourceException;

/**
 * 邮箱验证码：与短信分 Redis 命名空间；TTL、60s 冷却、按邮箱日限、IP/设备限流。
 */
class MemberEmailVerificationService
{
    /** @deprecated 使用 PURPOSE_ACTIVATE；保留常量以免历史 Redis 键文档引用断裂 */
    public const PURPOSE_SIGN = 'sign';

    /** 限流/冷却 Redis 键片段：激活 **链接** 邮件（非 6 位码） */
    public const PURPOSE_ACTIVATE = 'activate';

    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_FORGOT_PASSWORD = 'forgot_password';

    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function verifyCode(int $companyId, string $email, string $purpose, string $code): bool
    {
        $email = $this->normalizeEmail($email);
        $key = $this->codeKey($companyId, $purpose, $email);
        $redis = app('redis')->connection('members');
        $expect = $redis->get($key);

        return $expect !== null && $expect !== false && hash_equals((string) $expect, (string) $code);
    }

    public function consumeCode(int $companyId, string $email, string $purpose, string $code): bool
    {
        if (!$this->verifyCode($companyId, $email, $purpose, $code)) {
            return false;
        }
        $email = $this->normalizeEmail($email);
        $key = $this->codeKey($companyId, $purpose, $email);
        app('redis')->connection('members')->del($key);

        return true;
    }

    /**
     * 生成并发送 6 位验证码邮件。
     *
     * @return string 明文验证码（仅用于测试；生产不返回）
     */
    public function sendVerificationCode(
        int $companyId,
        string $email,
        string $purpose,
        string $clientIp,
        ?string $deviceId
    ): string {
        $email = $this->normalizeEmail($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ResourceException(trans('MembersBundle/Members.invalid_email'));
        }
        if ($purpose !== self::PURPOSE_LOGIN) {
            throw new ResourceException(trans('MembersBundle/Members.email_code_purpose_invalid'));
        }

        $this->assertCanSendEmailPurpose($companyId, $purpose, $email, $clientIp, $deviceId);

        $redis = app('redis')->connection('members');
        $code = (string) random_int(100000, 999999);
        $ttl = (int) (config('common.member_email_vcode_ttl') ?: 600);
        $redis->setex($this->codeKey($companyId, $purpose, $email), $ttl, $code);
        $this->setPurposeSendCooldown($companyId, $purpose, $email);

        $this->sendMail($companyId, $email, $purpose, $code, $ttl);

        return $code;
    }

    /**
     * 发 **登录** 6 位码或 **激活链接** 邮件前：登录 60s 冷却；激活链接为 `member_email_activation_cooldown_seconds`（默认 90s）+ 日限/IP/设备限流。
     */
    public function assertCanSendEmailPurpose(
        int $companyId,
        string $purpose,
        string $normalizedEmail,
        string $clientIp,
        ?string $deviceId
    ): void {
        $redis = app('redis')->connection('members');
        $cooldownKey = $this->cooldownKey($companyId, $purpose, $normalizedEmail);
        if ($redis->exists($cooldownKey)) {
            throw new ResourceException(trans('MembersBundle/Members.email_code_resend_too_fast'));
        }
        $this->assertRateLimits($companyId, $purpose, $normalizedEmail, $clientIp, $deviceId);
    }

    public function setPurposeSendCooldown(int $companyId, string $purpose, string $normalizedEmail): void
    {
        $redis = app('redis')->connection('members');
        $ttl = 60;
        if ($purpose === self::PURPOSE_ACTIVATE) {
            $ttl = (int) (config('common.member_email_activation_cooldown_seconds') ?: 90);
            if ($ttl < 1) {
                $ttl = 90;
            }
        }
        $redis->setex($this->cooldownKey($companyId, $purpose, $normalizedEmail), $ttl, '1');
    }

    private function assertRateLimits(int $companyId, string $purpose, string $email, string $clientIp, ?string $deviceId): void
    {
        $redis = app('redis')->connection('members');
        $day = date('Ymd');
        $limit = (int) (config('common.member_email_send_limit_per_day') ?: config('common.sms_send_limit') ?: 5);

        $emailKey = 'yzmemail:' . $companyId . ':' . $day . ':' . $purpose . ':' . sha1($email);
        $n = $redis->incr($emailKey);
        if ($redis->ttl($emailKey) === -1) {
            $redis->expire($emailKey, 3600 * 24);
        }
        if ($n > $limit) {
            throw new ResourceException(trans('MembersBundle/Members.email_send_limit_exceeded'));
        }

        if ($clientIp !== '') {
            $ipKey = 'member:email:ip:' . $companyId . ':' . $day . ':' . sha1($clientIp);
            $ipN = $redis->incr($ipKey);
            if ($redis->ttl($ipKey) === -1) {
                $redis->expire($ipKey, 3600 * 24);
            }
            $ipLimit = (int) (config('common.member_email_ip_limit_per_day') ?: 200);
            if ($ipN > $ipLimit) {
                throw new ResourceException(trans('MembersBundle/Members.email_ip_limit_exceeded'));
            }
        }

        if ($deviceId !== null && $deviceId !== '') {
            $devKey = 'member:email:dev:' . $companyId . ':' . $day . ':' . sha1($deviceId);
            $dN = $redis->incr($devKey);
            if ($redis->ttl($devKey) === -1) {
                $redis->expire($devKey, 3600 * 24);
            }
            $devLimit = (int) (config('common.member_email_device_limit_per_day') ?: 200);
            if ($dN > $devLimit) {
                throw new ResourceException(trans('MembersBundle/Members.email_device_limit_exceeded'));
            }
        }
    }

    private function codeKey(int $companyId, string $purpose, string $normalizedEmail): string
    {
        return 'member:email:vcode:' . sha1((string) $companyId) . ':' . $purpose . ':' . sha1($normalizedEmail);
    }

    private function cooldownKey(int $companyId, string $purpose, string $normalizedEmail): string
    {
        return 'member:email:vcode_cd:' . sha1((string) $companyId) . ':' . $purpose . ':' . sha1($normalizedEmail);
    }

    private function sendMail(int $companyId, string $to, string $purpose, string $code, int $ttlSeconds): void
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
        if ($purpose === self::PURPOSE_LOGIN) {
            $brand = (new CompanySettingBrandNameService())->resolveForCompanyId($companyId);
            $subjectBrand = str_replace(["\r", "\n"], '', strip_tags($brand));
            $subject = trans('MembersBundle/Members.email_login_vcode_headline_prefix') . $subjectBrand;
            $ttlMinutes = max(1, (int) ceil($ttlSeconds / 60));
            $body = view('members.email_login_vcode', [
                'brand' => $brand,
                'code' => $code,
                'ttlMinutes' => $ttlMinutes,
            ])->render();
        } else {
            $subject = trans('MembersBundle/Members.email_vcode_subject_' . $purpose);
            $body = trans('MembersBundle/Members.email_vcode_body', ['code' => $code]);
        }

        $mailer = new MailerService($configMail);
        if (!$mailer->doSend($to, $subject, $body)) {
            throw new ResourceException(trans('MembersBundle/Members.email_send_failed'));
        }
    }
}
