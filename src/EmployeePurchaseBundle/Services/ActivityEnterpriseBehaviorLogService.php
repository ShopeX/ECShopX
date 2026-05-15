<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Entities\Activities;
use EmployeePurchaseBundle\Entities\ActivityEnterpriseBehaviorLog;
use EmployeePurchaseBundle\Entities\ActivityEnterprises;
use EmployeePurchaseBundle\Entities\OrdersRelActivity;
use OrdersBundle\Services\OrderAssociationService;

class ActivityEnterpriseBehaviorLogService
{
    public const BEHAVIOR_SCAN = 'scan';

    public const BEHAVIOR_PASSPHRASE_VERIFY = 'passphrase_verify';

    public const BEHAVIOR_BIND = 'bind';

    public const BEHAVIOR_ORDER = 'order';

    /** {@see writeBehaviorLog} 写入 bind 流水时，extra 中绑定方式字段名，取值与 employee/auth 的 auth_type 一致 */
    public const EXTRA_KEY_BIND_CHANNEL = 'bind_channel';

    /** 扫码绑定（与 FrontApi `auth_type=qr_code` 一致） */
    public const BIND_CHANNEL_QR_CODE = 'qr_code';

    /** 口令活动：加车/下单前自动建档写入 bind 流水时的 bind_channel */
    public const BIND_CHANNEL_PASSPHRASE = 'passphrase';

    /** 口令验证等行为流水：验证成功 */
    public const RESULT_SUCCESS = 'success';

    /** 口令验证等行为流水：验证失败 */
    public const RESULT_FAIL = 'fail';

    /** @var \EmployeePurchaseBundle\Repositories\ActivityEnterpriseBehaviorLogRepository */
    public $behaviorLogRepository;

    /** @var \EmployeePurchaseBundle\Repositories\ActivitiesRepository */
    public $activitiesRepository;

    /** @var \EmployeePurchaseBundle\Repositories\ActivityEnterprisesRepository */
    public $activityEnterprisesRepository;

    public function __construct()
    {
        $em = app('registry')->getManager('default');
        $this->behaviorLogRepository = $em->getRepository(ActivityEnterpriseBehaviorLog::class);
        $this->activitiesRepository = $em->getRepository(Activities::class);
        $this->activityEnterprisesRepository = $em->getRepository(ActivityEnterprises::class);
    }

    /**
     * @return string[]
     */
    public static function allowedBehaviorTypes()
    {
        return [
            self::BEHAVIOR_SCAN,
            self::BEHAVIOR_PASSPHRASE_VERIFY,
            self::BEHAVIOR_BIND,
            self::BEHAVIOR_ORDER,
        ];
    }

    /**
     * @return string[]
     */
    public static function allowedResultStatuses()
    {
        return [self::RESULT_SUCCESS, self::RESULT_FAIL];
    }

    /**
     * 写入一条活动-企业行为流水（推荐在业务代码中统一调用本方法）
     *
     * @param int         $companyId
     * @param int         $activityId
     * @param int         $enterpriseId
     * @param string      $behaviorType {@see self::BEHAVIOR_SCAN} 等
     * @param int|null    $userId       已登录传会员 user_id
     * @param string|null $visitorKey   未登录扫码等场景 UV 去重，最长 64，建议 openid 摘要
     * @param int|null    $refId        如订单 ID
     * @param array|null  $extra        扩展字段，会存为 JSON
     * @param string|null $resultStatus {@see self::RESULT_SUCCESS} / {@see self::RESULT_FAIL}，当前仅 {@see self::BEHAVIOR_PASSPHRASE_VERIFY} 必填
     * @return int 新插入记录主键 id
     */
    public function writeBehaviorLog($companyId, $activityId, $enterpriseId, $behaviorType, $userId = null, $visitorKey = null, $refId = null, array $extra = null, $resultStatus = null)
    {
        $behaviorType = (string) $behaviorType;
        if (!in_array($behaviorType, self::allowedBehaviorTypes(), true)) {
            throw new ResourceException('无效的行为类型');
        }
        $companyId = (int) $companyId;
        $activityId = (int) $activityId;
        $enterpriseId = (int) $enterpriseId;
        if ($companyId <= 0 || $activityId <= 0 || $enterpriseId <= 0) {
            throw new ResourceException('参数错误');
        }
        $resultStatus = $resultStatus !== null && $resultStatus !== '' ? (string) $resultStatus : null;
        if ($behaviorType === self::BEHAVIOR_PASSPHRASE_VERIFY) {
            if ($resultStatus === null || !in_array($resultStatus, self::allowedResultStatuses(), true)) {
                throw new ResourceException('口令验证流水须指定 result_status 为 success 或 fail');
            }
        } elseif ($resultStatus !== null) {
            throw new ResourceException('当前仅口令验证行为可写 result_status');
        }
        $row = [
            'company_id' => $companyId,
            'activity_id' => $activityId,
            'enterprise_id' => $enterpriseId,
            'behavior_type' => $behaviorType,
            'created' => time(),
        ];
        if ($resultStatus !== null) {
            $row['result_status'] = $resultStatus;
        } else {
            $row['result_status'] = null;
        }
        if ($userId !== null && $userId !== '') {
            $row['user_id'] = (int) $userId;
        } else {
            $row['user_id'] = null;
        }
        if ($visitorKey !== null && $visitorKey !== '') {
            $row['visitor_key'] = substr((string) $visitorKey, 0, 64);
        } else {
            $row['visitor_key'] = null;
        }
        if ($refId !== null && $refId !== '') {
            $row['ref_id'] = (int) $refId;
        } else {
            $row['ref_id'] = null;
        }
        if ($extra !== null && $extra !== []) {
            $row['extra'] = json_encode($extra, JSON_UNESCAPED_UNICODE);
        } else {
            $row['extra'] = null;
        }

        return $this->behaviorLogRepository->insertRow($row);
    }

    /**
     * 同 {@see writeBehaviorLog}，保留别名便于旧文档或调用处兼容
     */
    public function record($companyId, $activityId, $enterpriseId, $behaviorType, $userId = null, $visitorKey = null, $refId = null, array $extra = null, $resultStatus = null)
    {
        return $this->writeBehaviorLog($companyId, $activityId, $enterpriseId, $behaviorType, $userId, $visitorKey, $refId, $extra, $resultStatus);
    }

    /**
     * 内购订单支付成功后记录 behavior_type=order（ref_id=订单号）。
     *
     * **如何认定内购订单**：`OrderAssociationService::getOrder` 返回的 **`orders_associations`** 行为 **`order_type=normal` 且 `order_class=employee_purchase`**
     *（与 {@see GetOrderServiceTrait::getOrderServiceByOrderInfo()} 拼出的 `normal_employee_purchase` 一致；库表不存拼接字符串）。
     * 再读 **`employee_purchase_orders_rel_activity`**（下单时 `NormalOrderService::createExtend` 已写入 `activity_id`/`enterprise_id`/`user_id`）。
     * 若无关联行或非内购订单类型，则不写流水。
     *
     * **下单统计口径**：内购订单支付成功且通过上述校验后写入 `order` 流水；管理端「下单人数」为该流水按 `user_id` 去重聚合，**不依赖**绑定渠道（扫码/口令等）。
     *
     * 幂等：同一订单已成功写入过 order 流水则跳过。取消/退款等不删除、不冲正流水。
     *
     * @param int|string $orderId 订单号
     * @param OrderAssociationService|null $orderAssociationService 测试注入；默认 `new OrderAssociationService()`
     */
    public function recordEmployeePurchaseOrderPaid($companyId, $orderId, OrderAssociationService $orderAssociationService = null): void
    {
        $companyId = (int) $companyId;
        if ($companyId <= 0 || $orderId === null || $orderId === '') {
            return;
        }

        if ($orderAssociationService === null) {
            $orderAssociationService = new OrderAssociationService();
        }
        $order = $orderAssociationService->getOrder($companyId, $orderId);
        if (empty($order) || !$this->isEmployeePurchaseAssociationOrder($order)) {
            return;
        }

        $em = app('registry')->getManager('default');
        /** @var \EmployeePurchaseBundle\Repositories\OrdersRelActivityRepository $ordersRelRepo */
        $ordersRelRepo = $em->getRepository(OrdersRelActivity::class);
        $rel = $ordersRelRepo->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
        if (empty($rel)) {
            return;
        }

        $activityId = (int) ($rel['activity_id'] ?? 0);
        $enterpriseId = (int) ($rel['enterprise_id'] ?? 0);
        $userId = (int) ($rel['user_id'] ?? 0);
        if ($activityId <= 0 || $enterpriseId <= 0 || $userId <= 0) {
            return;
        }

        $refId = (int) $orderId;
        if ($refId <= 0 && (string) $orderId !== '0') {
            return;
        }

        $dup = $this->behaviorLogRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
            'enterprise_id' => $enterpriseId,
            'behavior_type' => self::BEHAVIOR_ORDER,
            'ref_id' => $refId,
        ], 'id');
        if (!empty($dup)) {
            return;
        }

        $this->writeBehaviorLog(
            $companyId,
            $activityId,
            $enterpriseId,
            self::BEHAVIOR_ORDER,
            $userId,
            null,
            $refId,
            null
        );
    }

    /**
     * 是否与 {@see GetOrderServiceTrait::getOrderService('normal_employee_purchase')} 为同一类订单（库表拆开存，非拼接字段）。
     *
     * @param array<string,mixed> $order OrderAssociationService::getOrder 单行
     */
    private function isEmployeePurchaseAssociationOrder(array $order): bool
    {
        return ($order['order_type'] ?? '') === 'normal' && ($order['order_class'] ?? '') === 'employee_purchase';
    }

    /**
     * 管理端：活动下各参与企业的行为聚合（实时查流水表）
     *
     * @param int      $companyId
     * @param int      $activityId
     * @param int|null $distributorScopeId 店铺账号时传入 distributor_id
     * @return array{list: array<int,array<string,mixed>>}
     */
    public function getAggregatedStatsForAdmin($companyId, $activityId, $distributorScopeId = null)
    {
        $companyId = (int) $companyId;
        $activityId = (int) $activityId;
        $filter = ['company_id' => $companyId, 'id' => $activityId];
        if ($distributorScopeId !== null) {
            $filter['distributor_id'] = (int) $distributorScopeId;
        }
        $activity = $this->activitiesRepository->getInfo($filter);
        if (empty($activity)) {
            throw new ResourceException('活动不存在');
        }

        $participations = $this->activityEnterprisesRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
        ], 'enterprise_id', 1, -1, ['enterprise_id' => 'ASC']);

        $logRows = $this->behaviorLogRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityId,
        ], 'id, enterprise_id, behavior_type, result_status, user_id, visitor_key');

        $aggMap = $this->aggregateBehaviorStatsByEnterprise($logRows);

        $eids = [];
        foreach ($participations as $p) {
            $eids[] = (int) ($p['enterprise_id'] ?? 0);
        }
        $enterprisesService = new EnterprisesService();
        $entMap = $enterprisesService->getEnterpriseInfoBatchMap($companyId, $eids);

        $list = [];
        foreach ($participations as $p) {
            $eid = (int) ($p['enterprise_id'] ?? 0);
            if ($eid <= 0) {
                continue;
            }
            $row = $aggMap[$eid] ?? [
                'scan_pv' => 0,
                'scan_uv' => 0,
                'passphrase_verify_uv' => 0,
                'bind_uv' => 0,
                'order_uv' => 0,
            ];
            $ent = $entMap[$eid] ?? [];
            $list[] = [
                'enterprise_id' => $eid,
                'enterprise_name' => $ent['name'] ?? '',
                'enterprise_sn' => $ent['enterprise_sn'] ?? '',
                'logo' => $ent['logo'] ?? '',
                'scan_count' => (int) $row['scan_pv'],
                'scan_user_count' => (int) $row['scan_uv'],
                'passphrase_verify_user_count' => (int) $row['passphrase_verify_uv'],
                'bind_user_count' => (int) $row['bind_uv'],
                'order_user_count' => (int) $row['order_uv'],
            ];
        }

        return ['list' => $list];
    }

    /**
     * 管理端活动列表：按活动 ID 聚合整活动维度统计（与单活动内各企业之和的口径不同，UV 为活动内去重）
     *
     * @param int        $companyId
     * @param int[]      $activityIds
     * @return array<int, array{scan_count:int, scan_user_count:int, passphrase_verify_user_count:int, bind_user_count:int, order_user_count:int}>
     */
    public function getAggregatedStatsTotalsByActivityIds($companyId, array $activityIds)
    {
        $companyId = (int) $companyId;
        $activityIds = array_values(array_unique(array_filter(array_map('intval', $activityIds))));
        if ($companyId <= 0 || $activityIds === []) {
            return [];
        }

        $logRows = $this->behaviorLogRepository->getLists([
            'company_id' => $companyId,
            'activity_id' => $activityIds,
        ], 'id, activity_id, enterprise_id, behavior_type, result_status, user_id, visitor_key');

        $byActivity = [];
        foreach ($logRows as $r) {
            $aid = (int) ($r['activity_id'] ?? 0);
            if ($aid <= 0) {
                continue;
            }
            if (!isset($byActivity[$aid])) {
                $byActivity[$aid] = [];
            }
            $byActivity[$aid][] = $r;
        }

        $out = [];
        foreach ($activityIds as $aid) {
            $subset = $byActivity[$aid] ?? [];
            $stats = $this->computeEnterpriseBehaviorStats($subset);
            $out[$aid] = [
                'scan_count' => (int) $stats['scan_pv'],
                'scan_user_count' => (int) $stats['scan_uv'],
                'passphrase_verify_user_count' => (int) $stats['passphrase_verify_uv'],
                'bind_user_count' => (int) $stats['bind_uv'],
                'order_user_count' => (int) $stats['order_uv'],
            ];
        }

        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $logRows
     * @return array<int,array{scan_pv:int,scan_uv:int,passphrase_verify_uv:int,bind_uv:int,order_uv:int}>
     */
    private function aggregateBehaviorStatsByEnterprise(array $logRows)
    {
        $byEnterprise = [];
        foreach ($logRows as $r) {
            $eid = (int) ($r['enterprise_id'] ?? 0);
            if ($eid <= 0) {
                continue;
            }
            if (!isset($byEnterprise[$eid])) {
                $byEnterprise[$eid] = [];
            }
            $byEnterprise[$eid][] = $r;
        }

        $map = [];
        foreach ($byEnterprise as $eid => $rows) {
            $map[$eid] = $this->computeEnterpriseBehaviorStats($rows);
        }

        return $map;
    }

    /**
     * @param array<int,array<string,mixed>> $rows 同一 enterprise_id 下的流水
     * @return array{scan_pv:int,scan_uv:int,passphrase_verify_uv:int,bind_uv:int,order_uv:int}
     */
    private function computeEnterpriseBehaviorStats(array $rows)
    {
        return [
            'scan_pv' => $this->countBehaviorEvents($rows, self::BEHAVIOR_SCAN),
            'scan_uv' => $this->countDistinctVisitorsForBehavior($rows, self::BEHAVIOR_SCAN),
            'passphrase_verify_uv' => $this->countDistinctVisitorsForPassphraseVerifySuccess($rows),
            'bind_uv' => $this->countDistinctVisitorsForBehavior($rows, self::BEHAVIOR_BIND),
            'order_uv' => $this->countDistinctVisitorsForBehavior($rows, self::BEHAVIOR_ORDER),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function countBehaviorEvents(array $rows, $behaviorType)
    {
        $n = 0;
        foreach ($rows as $r) {
            if (($r['behavior_type'] ?? '') === $behaviorType) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function countDistinctVisitorsForBehavior(array $rows, $behaviorType)
    {
        $keys = [];
        foreach ($rows as $r) {
            if (($r['behavior_type'] ?? '') !== $behaviorType) {
                continue;
            }
            $keys[$this->visitorDistinctKey($r)] = true;
        }

        return count($keys);
    }

    /**
     * 口令验证成功人数(UV)；result_status 为 success，历史 NULL 视为成功（兼容迁移前数据）
     *
     * @param array<int,array<string,mixed>> $rows
     */
    private function countDistinctVisitorsForPassphraseVerifySuccess(array $rows)
    {
        $keys = [];
        foreach ($rows as $r) {
            if (($r['behavior_type'] ?? '') !== self::BEHAVIOR_PASSPHRASE_VERIFY) {
                continue;
            }
            $rs = $r['result_status'] ?? null;
            if ($rs === self::RESULT_FAIL) {
                continue;
            }
            if ($rs !== null && $rs !== '' && $rs !== self::RESULT_SUCCESS) {
                continue;
            }
            $keys[$this->visitorDistinctKey($r)] = true;
        }

        return count($keys);
    }

    /**
     * 与原先 SQL 中 UV 规则一致：优先 user_id，其次 visitor_key，否则用行 id
     *
     * @param array<string,mixed> $row
     */
    private function visitorDistinctKey(array $row)
    {
        $uid = (int) ($row['user_id'] ?? 0);
        if ($uid > 0) {
            return 'u'.$uid;
        }
        $vk = trim((string) ($row['visitor_key'] ?? ''));
        if ($vk !== '') {
            return 'v'.$vk;
        }

        return 'i'.(int) ($row['id'] ?? 0);
    }
}
