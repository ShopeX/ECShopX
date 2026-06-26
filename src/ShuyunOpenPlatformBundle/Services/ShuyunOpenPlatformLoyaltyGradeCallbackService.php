<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use InvalidArgumentException;
use KaquanBundle\Services\PackageSetService;
use MembersBundle\Entities\MemberOperateLog;
use MembersBundle\Repositories\MemberOperateLogRepository;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Services\Member\MemberCardGradeService;
use OpenapiBundle\Services\Member\MemberService as OpenapiMemberService;

/**
 * 数云「会员等级变更（路由模式）」回调：{@code shuyun.callback.loyalty.member.grade.change}。
 *
 * 请求体（路由模式 standard：grade、id、partner、shopId、platCode、occurDate、sequence、source 必填；{@code expired} / {@code expiredTime}、desc、omid、memberId 等可选——memberId 不参与会员定位）。
 * 数云「自定义/线下会员等级同步」模拟器常用别名会在 {@see applyShuyunOpenPlatformRoutingAliases} 中归一：
 * created/version→occurDate、id→sequence、changeType→source；partner 缺失时联调用占位。
 *
 * 会员定位：**仅** body **`id`** 作为商城 **`user_id`**（纯数字，不剥后缀）。**非路由**回调同样须带 **`id`**。
 * **sequence** 常由 **id** 映射；报文中仍以数云原始 **id** 为准（含后缀）；不做 Redis 去重，同一会员连续变更或数云重试均会再次执行更新。
 *
 * 等级映射：数云路由体 {@code grade} 为**纯数字**时，优先与本地等级档案 {@code promotion_condition.total_consumption}（层级序号，与数云同步写入一致）匹配；
 * {@code membercard_grade.getList} 可能返回 JSON 字符串形态的 {@code promotion_condition}，须解析后再读 {@code total_consumption}。
 * 无档位命中时再按 {@code external_id} 等于该数字回退（数云稳定 gradeId 与 external_id 一致的历史数据）。
 * 若解析出的目标本地 {@code grade_id} 与会员当前 {@code members.grade_id} 一致（含 KEEPING / 双平台重复推送），则**不**调用 {@code updateDetail}、不写会员操作日志，仅写 {@code shuyun_open_platform} 渠道 info 说明跳过原因。
 */
final class ShuyunOpenPlatformLoyaltyGradeCallbackService
{
    private OpenapiMemberService $openapiMemberService;

    private MemberCardGradeService $memberCardGradeService;

    public function __construct(
        OpenapiMemberService $openapiMemberService,
        MemberCardGradeService $memberCardGradeService
    ) {
        $this->openapiMemberService = $openapiMemberService;
        $this->memberCardGradeService = $memberCardGradeService;
    }

    /**
     * @param  array<string, mixed>  $body
     *
     * @return bool 无异常时为 true（含目标等级与当前一致而跳过、以及 updateDetail 返回空等边界）
     *
     * @throws InvalidArgumentException 参数不合法
     * @throws ErrorException            Openapi 会员/等级校验失败
     */
    public function applyGradeChange(int $companyId, array $body): bool
    {
        $p = $this->normalizePayload($body);

        if ($this->isRoutingModePayload($p)) {
            $p = $this->applyShuyunOpenPlatformRoutingAliases($p);
            $this->assertRoutingCallbackRequiredFields($p);
        }

        $gradeRaw = $this->extractGradeRaw($p);
        $resolved = $this->resolveLocalGradeRowMeta($companyId, $gradeRaw);
        $localGrade = $resolved['row'];
        $localGradeId = (string) ($localGrade['grade_id'] ?? '');
        if ($localGradeId === '') {
            throw new InvalidArgumentException('GRADE_NOT_MAPPED');
        }

        $userId = $this->resolveUserId($companyId, $p);
        $memberRow = $this->openapiMemberService->find([
            'company_id' => $companyId,
            'user_id' => $userId,
        ]);
        if ($memberRow === []) {
            throw new InvalidArgumentException('MEMBER_NOT_FOUND');
        }

        $oldGradeId = (int) ($memberRow['grade_id'] ?? 0);
        $targetGradeId = (int) $localGradeId;
        if ($targetGradeId === $oldGradeId) {
            $this->logGradeCallbackSkippedUnchanged($companyId, $userId, $oldGradeId, $gradeRaw, $resolved['via'], $p, $body);

            return true;
        }

        $updatePayload = $resolved['via'] === 'external'
            ? ['external_id' => (string) (int) $gradeRaw]
            : ['grade_id' => $localGradeId];

        $newUser = $this->openapiMemberService->updateDetail(
            ['company_id' => $companyId, 'user_id' => $userId],
            $updatePayload,
        );

        if ($newUser === []) {
            try {
                app('log')->channel('shuyun_open_platform')->warning('Shuyun loyalty grade callback: updateDetail returned empty (unexpected).', [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'update_payload' => $updatePayload,
                ]);
            } catch (\Throwable $e) {
            }

            return true;
        }

        try {
            app('log')->channel('shuyun_open_platform')->info('Shuyun loyalty grade callback: grade persisted.', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'resolved_via' => $resolved['via'],
                'shuyun_grade_raw' => $gradeRaw,
                'local_grade_id' => $localGradeId,
                'old_members_grade_id' => $oldGradeId,
                'new_members_grade_id' => (int) ($newUser['grade_id'] ?? 0),
            ]);
        } catch (\Throwable $e) {
        }

        $newGradeId = (int) ($newUser['grade_id'] ?? 0);
        if ($newGradeId > $oldGradeId) {
            (new PackageSetService())->triggerPackage($companyId, $userId, $newGradeId, 'grade', '1');
        }

        $this->writeOperateLog($companyId, $userId, (string) $oldGradeId, $gradeRaw, $localGradeId, $body);

        return true;
    }

    /**
     * @param  array<string, mixed>  $p        normalizePayload + 路由别名后的有效载荷（用于日志摘字段）
     * @param  array<string, mixed>  $rawBody  原始入参 body
     */
    private function logGradeCallbackSkippedUnchanged(
        int $companyId,
        int $userId,
        int $currentGradeId,
        string $gradeRaw,
        string $resolvedVia,
        array $p,
        array $rawBody,
    ): void {
        try {
            $platCode = isset($p['platCode']) ? trim((string) $p['platCode']) : '';
            app('log')->channel('shuyun_open_platform')->info('Shuyun loyalty grade callback: skipped (grade unchanged).', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'members_grade_id' => $currentGradeId,
                'resolved_via' => $resolvedVia,
                'shuyun_grade_raw' => $gradeRaw,
                'plat_code' => $platCode !== '' ? $platCode : null,
                'body_id' => isset($p['id']) ? trim((string) $p['id']) : null,
                'change_type' => isset($p['changeType']) ? trim((string) $p['changeType']) : (isset($p['source']) ? trim((string) $p['source']) : null),
                'sequence' => isset($p['sequence']) ? trim((string) $p['sequence']) : null,
                'body_excerpt' => $this->gradeCallbackLogBodyExcerpt($rawBody),
            ]);
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param  array<string, mixed>  $rawBody
     */
    private function gradeCallbackLogBodyExcerpt(array $rawBody): ?string
    {
        try {
            $json = json_encode($rawBody, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }
        $max = 2048;
        if (\strlen($json) <= $max) {
            return $json;
        }

        return substr($json, 0, $max).'…';
    }

    /**
     * @param  array<string, mixed>  $body
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $body): array
    {
        $p = $body;
        if (isset($body['data']) && \is_array($body['data']) && $body['data'] !== []) {
            $p = array_merge($body, $body['data']);
        }
        if (isset($p['member']) && \is_array($p['member'])) {
            $p = array_merge($p, $p['member']);
        }

        return $p;
    }

    /**
     * 将数云侧「自定义/线下会员等级同步」等非标准字段名映射为路由模式 canonical 键，便于与 {@see assertRoutingCallbackRequiredFields} 对齐。
     *
     * @param  array<string, mixed>  $p  已满足 {@see isRoutingModePayload}
     *
     * @return array<string, mixed>
     */
    private function applyShuyunOpenPlatformRoutingAliases(array $p): array
    {
        if ($this->routingFieldMissing($p, 'occurDate')) {
            foreach (['created', 'version'] as $from) {
                if (!$this->routingFieldMissing($p, $from)) {
                    $p['occurDate'] = trim((string) $p[$from]);
                    break;
                }
            }
        }

        if ($this->routingFieldMissing($p, 'sequence')) {
            $sid = isset($p['id']) ? trim((string) $p['id']) : '';
            if ($sid !== '') {
                $p['sequence'] = $sid;
            }
        }

        if ($this->routingFieldMissing($p, 'source')) {
            $ct = isset($p['changeType']) ? trim((string) $p['changeType']) : '';
            if ($ct !== '') {
                $p['source'] = $ct;
            }
        }

        if ($this->routingFieldMissing($p, 'partner')) {
            $p['partner'] = 'SHUYUN';
        }

        return $p;
    }

    /**
     * @param  array<string, mixed>  $p
     */
    private function isRoutingModePayload(array $p): bool
    {
        $pc = isset($p['platCode']) ? trim((string) $p['platCode']) : '';

        return $pc !== '';
    }

    /**
     * @param  array<string, mixed>  $p
     */
    private function assertRoutingCallbackRequiredFields(array $p): void
    {
        foreach (['grade', 'partner', 'shopId', 'platCode', 'occurDate', 'sequence', 'source', 'id'] as $k) {
            if ($this->routingFieldMissing($p, $k)) {
                throw new InvalidArgumentException('ROUTING_FIELD_REQUIRED:'.$k);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $p
     */
    private function routingFieldMissing(array $p, string $k): bool
    {
        if (!\array_key_exists($k, $p)) {
            return true;
        }
        $v = $p[$k];
        if ($v === null) {
            return true;
        }
        if (\is_string($v) && trim($v) === '') {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $p
     */
    private function extractGradeRaw(array $p): string
    {
        if (!$this->routingFieldMissing($p, 'grade')) {
            $g = $p['grade'];
            if (is_numeric($g)) {
                return (string) (int) $g;
            }
            $s = trim((string) $g);

            return $s !== '' ? $s : throw new InvalidArgumentException('GRADE_ID_REQUIRED');
        }

        foreach (['gradeId', 'newGradeId', 'afterGradeId', 'grade_id'] as $k) {
            if (!isset($p[$k]) || $p[$k] === '' || !is_numeric($p[$k])) {
                continue;
            }

            return (string) (int) $p[$k];
        }

        throw new InvalidArgumentException('GRADE_ID_REQUIRED');
    }

    /**
     * @param  array<string, mixed>  $p
     */
    private function resolveUserId(int $companyId, array $p): int
    {
        if ($this->routingFieldMissing($p, 'id')) {
            throw new InvalidArgumentException('MEMBER_ID_REQUIRED');
        }
        $platCodeUpper = isset($p['platCode']) ? strtoupper(trim((string) $p['platCode'])) : '';
        $rawId = trim((string) $p['id']);
        $candidate = $rawId;
        if ($candidate === '' || !ctype_digit($candidate)) {
            throw new InvalidArgumentException('MEMBER_ID_INVALID');
        }
        $uid = (int) $candidate;
        if ($uid < 1) {
            throw new InvalidArgumentException('MEMBER_ID_INVALID');
        }
        $row = $this->openapiMemberService->find([
            'company_id' => $companyId,
            'user_id' => $uid,
        ]);
        if ($row === []) {
            throw new InvalidArgumentException('MEMBER_NOT_FOUND');
        }

        return $uid;
    }

    /**
     * level：{@code promotion_condition.total_consumption} 与数云 {@code grade} 整数一致（优先，避免误把「层级 4」当成 {@code external_id}=4）。
     * external：无档位命中时按 {@code external_id} 回退。
     *
     * @return array{row: array<string, mixed>, via: 'external'|'level'}
     */
    private function resolveLocalGradeRowMeta(int $companyId, string $gradeRaw): array
    {
        if ($gradeRaw === '' || ! is_numeric($gradeRaw)) {
            throw new InvalidArgumentException('GRADE_NOT_MAPPED');
        }

        $level = (int) $gradeRaw;

        $listResult = $this->memberCardGradeService->list(
            ['company_id' => $companyId],
            1,
            200,
            [],
            '*',
            false,
        );
        foreach ($listResult['list'] ?? [] as $g) {
            $pc = $this->coercePromotionConditionToArray($g['promotion_condition'] ?? null);
            $tc = (int) ($pc['total_consumption'] ?? 0);
            if ($tc === $level) {
                return ['row' => $g, 'via' => 'level'];
            }
        }

        $extKey = (string) $level;
        if ($extKey !== '0') {
            $byExternal = $this->memberCardGradeService->find([
                'company_id' => $companyId,
                'external_id' => $extKey,
            ]);
            if ($byExternal !== []) {
                return ['row' => $byExternal, 'via' => 'external'];
            }
        }

        throw new InvalidArgumentException('GRADE_NOT_MAPPED');
    }

    /**
     * @param  mixed  $promotionCondition  DB 行上常为 array；直连 SQL 列表时可能为 JSON 字符串
     *
     * @return array<string, mixed>
     */
    private function coercePromotionConditionToArray($promotionCondition): array
    {
        if (\is_array($promotionCondition)) {
            return $promotionCondition;
        }
        if (\is_string($promotionCondition) && trim($promotionCondition) !== '') {
            try {
                $decoded = json_decode($promotionCondition, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return [];
            }
            if (\is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $rawBody
     */
    private function writeOperateLog(
        int $companyId,
        int $userId,
        string $oldGradeId,
        string $gradeRaw,
        string $newLocalGradeId,
        array $rawBody
    ): void {
        try {
            $repo = app('registry')->getManager('default')->getRepository(MemberOperateLog::class);
            if (!$repo instanceof MemberOperateLogRepository) {
                return;
            }
            $repo->create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'operate_type' => 'grade_id',
                'old_data' => $oldGradeId,
                'new_data' => json_encode([
                    'shuyun_grade' => $gradeRaw,
                    'local_grade_id' => $newLocalGradeId,
                ], JSON_UNESCAPED_UNICODE),
                'operater' => '数云开放网关',
                'remarks' => (string) json_encode($rawBody, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            app('log')->debug('Shuyun loyalty grade callback: operate log skipped: '.$e->getMessage());
        }
    }
}
