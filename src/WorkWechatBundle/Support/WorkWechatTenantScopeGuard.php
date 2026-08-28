<?php

declare(strict_types=1);

namespace WorkWechatBundle\Support;

use Dingo\Api\Exception\ResourceException;

/**
 * WorkWechat tenant-scope gate: bind auth company_id on corp cache writes.
 */
class WorkWechatTenantScopeGuard
{
    public static function assertCorpidAvailableForCompany(int $companyId, string $corpid): void
    {
        if ($companyId <= 0 || $corpid === '') {
            throw new ResourceException('无权保存该企业微信配置');
        }

        $redis = app('redis')->connection('default');
        $existing = $redis->get(self::corpidCacheKey($corpid));
        if (!$existing) {
            return;
        }

        $data = json_decode($existing, true);
        if (!is_array($data)) {
            return;
        }

        $existingCompanyId = (int) ($data['company_id'] ?? 0);
        if ($existingCompanyId > 0 && $existingCompanyId !== $companyId) {
            throw new ResourceException('该企业微信corpid已被其他租户绑定');
        }
    }

    public static function corpidCacheKey(string $corpid): string
    {
        return 'workwechat:configcropid:' . $corpid;
    }

    /**
     * @param array<string, mixed>|null $existing
     */
    public static function assertVerifyDomainFileAvailableForCompany(int $companyId, ?array $existing): void
    {
        if ($companyId <= 0 || empty($existing)) {
            return;
        }

        $existingCompanyId = (int) ($existing['company_id'] ?? 0);
        if ($existingCompanyId > 0 && $existingCompanyId !== $companyId) {
            throw new ResourceException('该域名校验文件已被其他租户使用');
        }
    }
}
