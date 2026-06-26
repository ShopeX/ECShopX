<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * 口令企业「可参与名额」Redis 缓存：与 employee_purchase_activity_passphrase_enterprises.participate_quota 对齐。
 * 消耗口径：每次成功绑定（员工身份认证成功且记活动）扣 1；每笔未取消的内购订单在提交事务前扣 1（已在
 * {@see ActivityEnterpriseParticipateUser} 表中的用户跳过校验与扣减）。
 * 管理端保存/替换口令企业后调用 syncRemainingAfterQuotaSettingChange 按配置差额更新 Redis 剩余；
 * 订单关联表 participate_quota_order_consumed 标记本单是否扣过名额以便取消时释放 Redis。
 */

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Entities\Activities;
use EmployeePurchaseBundle\Entities\ActivityEnterpriseParticipateUser;
use EmployeePurchaseBundle\Entities\ActivityPassphraseEnterprises;

class PassphraseParticipateQuotaRedisService
{
    public const REDIS_KEY_PREFIX = 'ep:pquota:';

    private static function luaConsume(): string
    {
        return <<<'LUA'
local cur = redis.call('GET', KEYS[1])
if cur == false then return -1 end
local n = tonumber(cur)
if n == nil or n < 1 then return 0 end
redis.call('DECR', KEYS[1])
return 1
LUA;
    }

    public static function redisKey(int $companyId, int $activityId, int $enterpriseId): string
    {
        return self::REDIS_KEY_PREFIX.$companyId.':'.$activityId.':'.$enterpriseId;
    }

    /**
     * 是否对该活动-企业启用名额（活动开启口令且口令表有配置）。
     */
    public function isApplicable(int $companyId, int $activityId, int $enterpriseId): bool
    {
        if ($companyId <= 0 || $activityId <= 0 || $enterpriseId <= 0) {
            return false;
        }
        $em = app('registry')->getManager('default');
        /** @var \EmployeePurchaseBundle\Repositories\ActivitiesRepository $actRepo */
        $actRepo = $em->getRepository(Activities::class);
        $activity = $actRepo->getInfo(['company_id' => $companyId, 'id' => $activityId]);
        if (empty($activity) || empty($activity['is_passphrase_enabled'])) {
            return false;
        }
        /** @var \EmployeePurchaseBundle\Repositories\ActivityPassphraseEnterprisesRepository $pRepo */
        $pRepo = $em->getRepository(ActivityPassphraseEnterprises::class);
        $rows = $pRepo->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
            'enterprise_id' => $enterpriseId,
        ], 'participate_quota');
        if (empty($rows)) {
            return false;
        }

        return (int) ($rows[0]['participate_quota'] ?? 0) > 0;
    }

    /**
     * 管理端保存口令企业配置：DB 存配置总额，Redis 剩余 += (新配置 - 原配置)。
     */
    public function syncRemainingAfterQuotaSettingChange(
        int $companyId,
        int $activityId,
        int $enterpriseId,
        int $oldQuota,
        int $newQuota
    ): void {
        if ($newQuota <= 0) {
            $this->deleteKey($companyId, $activityId, $enterpriseId);

            return;
        }
        $key = self::redisKey($companyId, $activityId, $enterpriseId);
        $redis = app('redis');
        $delta = $newQuota - $oldQuota;
        if ($oldQuota <= 0) {
            $cur = $redis->get($key);
            if ($cur === false || $cur === null) {
                $redis->set($key, (string) $newQuota);

                return;
            }
        }
        if ($delta === 0) {
            return;
        }
        $redis->incrby($key, $delta);
        if ((int) $redis->get($key) < 0) {
            $redis->set($key, '0');
        }
    }

    /**
     * 从 DB 重算并写入剩余名额（不参与 Lua，仅 SET）。
     */
    public function warmEnterprise(int $companyId, int $activityId, int $enterpriseId, int $participateQuota): void
    {
        if ($participateQuota <= 0) {
            app('redis')->del([self::redisKey($companyId, $activityId, $enterpriseId)]);

            return;
        }
        $em = app('registry')->getManager('default');
        /** @var \EmployeePurchaseBundle\Repositories\ActivityEnterpriseParticipateUserRepository $participateUserRepo */
        $participateUserRepo = $em->getRepository(ActivityEnterpriseParticipateUser::class);
        $used = $participateUserRepo->countForActivityEnterprise($companyId, $activityId, $enterpriseId);
        $calculated = $participateQuota - $used;
        if ($calculated < 0) {
            $calculated = 0;
        }
        $key = self::redisKey($companyId, $activityId, $enterpriseId);
        $redis = app('redis');
        $cur = $redis->get($key);
        $remain = ($cur !== false && $cur !== null) ? min((int) $cur, $calculated) : $calculated;
        $redis->set($key, (string) $remain);
    }

    public function deleteKey(int $companyId, int $activityId, int $enterpriseId): void
    {
        app('redis')->del([self::redisKey($companyId, $activityId, $enterpriseId)]);
    }

    /**
     * 确保 key 存在后再扣减；返回是否扣减成功。
     */
    public function tryConsumeSlot(int $companyId, int $activityId, int $enterpriseId): bool
    {
        if (!$this->isApplicable($companyId, $activityId, $enterpriseId)) {
            return true;
        }
        $key = self::redisKey($companyId, $activityId, $enterpriseId);
        $redis = app('redis');
        $cur = $redis->get($key);
        if ($cur === false || $cur === null) {
            $this->warmFromPassphraseRow($companyId, $activityId, $enterpriseId);
        }
        $r = (int) $redis->eval(self::luaConsume(), 1, $key);
        if ($r === -1) {
            $this->warmFromPassphraseRow($companyId, $activityId, $enterpriseId);
            $r = (int) $redis->eval(self::luaConsume(), 1, $key);
        }

        return $r === 1;
    }

    /**
     * 取消订单等场景释放一笔订单占用的名额（与 tryConsumeSlot 对称）。
     */
    public function releaseOneSlot(int $companyId, int $activityId, int $enterpriseId): void
    {
        if (!$this->isApplicable($companyId, $activityId, $enterpriseId)) {
            return;
        }
        $key = self::redisKey($companyId, $activityId, $enterpriseId);
        $redis = app('redis');
        $cur = $redis->get($key);
        if ($cur === false || $cur === null) {
            $this->warmFromPassphraseRow($companyId, $activityId, $enterpriseId);
        }
        $redis->incr($key);
    }

    private function warmFromPassphraseRow(int $companyId, int $activityId, int $enterpriseId): void
    {
        $em = app('registry')->getManager('default');
        /** @var \EmployeePurchaseBundle\Repositories\ActivityPassphraseEnterprisesRepository $pRepo */
        $pRepo = $em->getRepository(ActivityPassphraseEnterprises::class);
        $rows = $pRepo->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
            'enterprise_id' => $enterpriseId,
        ], 'participate_quota');
        if (empty($rows)) {
            throw new ResourceException('口令企业配置不存在');
        }
        $quota = (int) ($rows[0]['participate_quota'] ?? 0);
        $this->warmEnterprise($companyId, $activityId, $enterpriseId, $quota);
    }
}
