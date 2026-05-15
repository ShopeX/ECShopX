<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * 已登录用户在某活动+企业下「口令校验成功」后的短期标记，供 C 端二次进入时跳过再次输入口令。
 * TTL 与活动结束时间对齐（上限 90 天），避免长期误放行。
 */

namespace EmployeePurchaseBundle\Services;

class PassphraseVerifiedRedisService
{
    private const KEY_PREFIX = 'ep_passphrase_verified:';

    public static function redisKey(int $companyId, int $activityId, int $enterpriseId, int $userId): string
    {
        return self::KEY_PREFIX.$companyId.':'.$activityId.':'.$enterpriseId.':'.$userId;
    }

    /**
     * 口令校验成功后调用（仅 userId>0 有效）。
     *
     * @param array<string,mixed> $activity {@see ActivitiesService::getInfo} 单行
     */
    public function markVerified(int $companyId, int $activityId, int $enterpriseId, int $userId, array $activity): void
    {
        if ($userId < 1 || $companyId < 1 || $activityId < 1 || $enterpriseId < 1) {
            return;
        }
        $ttl = $this->ttlSecondsUntilActivityEnds($activity);
        app('redis')->setex(self::redisKey($companyId, $activityId, $enterpriseId, $userId), $ttl, '1');
    }

    public function isVerified(int $companyId, int $activityId, int $enterpriseId, int $userId): bool
    {
        if ($userId < 1 || $companyId < 1 || $activityId < 1 || $enterpriseId < 1) {
            return false;
        }
        $v = app('redis')->get(self::redisKey($companyId, $activityId, $enterpriseId, $userId));

        return $v !== null && $v !== false && $v !== '';
    }

    /**
     * 清除某会员在某企业下、所有活动维度的口令已验 Redis 键；删员工后须重新 behavior-report 验口令。
     */
    public function forgetVerifiedForUserEnterprise(int $companyId, int $enterpriseId, int $userId): void
    {
        if ($userId < 1 || $companyId < 1 || $enterpriseId < 1) {
            return;
        }
        $pattern = self::KEY_PREFIX.$companyId.':*:'.$enterpriseId.':'.$userId;
        $redis = app('redis');
        $keys = $redis->keys($pattern);
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }

    /**
     * @param array<string,mixed> $activity
     */
    private function ttlSecondsUntilActivityEnds(array $activity): int
    {
        $end = (int) ($activity['employee_end_time'] ?? 0);
        if (!empty($activity['if_relative_join'])) {
            $re = (int) ($activity['relative_end_time'] ?? 0);
            $end = max($end, $re);
        }
        $now = time();
        $sec = $end > $now ? ($end - $now + 86400) : 86400;

        return min(max($sec, 3600), 90 * 86400);
    }
}
